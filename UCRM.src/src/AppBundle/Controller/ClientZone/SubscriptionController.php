<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller\ClientZone;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Form\PaymentPlanType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone/subscription")
 */
class SubscriptionController extends BaseController
{
    /**
     * @Route("/new", name="client_zone_subscription_new")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function newAction(Request $request): Response
    {
        $client = $this->getClient();

        $servicesForLinkedSubscriptions = $this->get(ServiceDataProvider::class)->getServicesForLinkedSubscriptions(
            $client
        );
        $linkedSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
            && $client->getOrganization()->hasPaymentProviderSupportingAutopay($this->isSandbox())
            && $servicesForLinkedSubscriptions;

        $customSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && $client->getOrganization()->hasPaymentGateway($this->isSandbox());

        if (! $linkedSubscriptionPossible && ! $customSubscriptionPossible) {
            throw $this->createAccessDeniedException();
        }

        $paymentPlanFacade = $this->get(PaymentPlanFacade::class);
        $paymentPlan = new PaymentPlan();
        $paymentPlanFacade->setDefaults($paymentPlan, $client);
        if (! $customSubscriptionPossible) {
            $paymentPlan->setAmountInSmallestUnit(null);
            $paymentPlan->setPeriod(null);
            $paymentPlan->setService(reset($servicesForLinkedSubscriptions));
        }

        $url = $this->generateUrl('client_zone_subscription_new');
        $form = $this->createForm(
            PaymentPlanType::class,
            $paymentPlan,
            [
                'action' => $url,
                'client' => $client,
                'smallest_unit_multiplier' => 10 ** $client->getOrganization()->getCurrency()->getFractionDigits(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $paymentPlanFacade->handleCreate($paymentPlan);

            return new JsonResponse(
                [
                    'redirect' => $this->generateUrl(
                        'online_payment_subscribe',
                        [
                            'id' => $paymentPlan->getId(),
                        ]
                    ),
                ]
            );
        }

        return $this->render(
            'payments/components/new_subscription.html.twig',
            [
                'paymentPlan' => $paymentPlan,
                'form' => $form->createView(),
                'client' => $client,
                'services' => $servicesForLinkedSubscriptions,
                'linkedSubscriptionPossible' => $linkedSubscriptionPossible,
                'customSubscriptionPossible' => $customSubscriptionPossible,
            ]
        );
    }

    /**
     * @Route("/{id}/cancel", name="client_zone_subscription_cancel", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function cancelAction(PaymentPlan $paymentPlan): Response
    {
        if (
            ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw $this->createAccessDeniedException();
        }

        $this->verifyOwnership($paymentPlan);

        try {
            $this->get(PaymentPlanFacade::class)->cancelSubscription($paymentPlan);
            $this->addTranslatedFlash('success', 'Your subscription was successfully cancelled.');
            $message['logMsg'] = [
                'message' => 'Subscription %s was canceled',
                'replacements' => $paymentPlan->getName(),
            ];

            $this->get(ActionLogger::class)->log(
                $message,
                $this->getUser(),
                $paymentPlan->getClient(),
                EntityLog::PAYMENT_PLAN_CANCELED
            );
        } catch (\Exception $exception) {
            $this->addTranslatedFlash(
                'danger',
                'An error occurred during subscription cancellation. Please try again later.'
            );
        }

        return $this->redirectToRoute('client_zone_client_index');
    }
}
