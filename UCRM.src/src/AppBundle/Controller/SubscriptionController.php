<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\Service;
use AppBundle\Exception\PaymentPlanException;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Form\PaymentPlanType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\ActionLogger;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing/payments/subscriptions")
 * @PermissionControllerName(PaymentController::class)
 */
class SubscriptionController extends BaseController
{
    /**
     * @Route(
     *     "/new/{id}/{serviceId}",
     *     name="payment_subscription_new",
     *     requirements={
     *         "id": "\d+",
     *         "serviceId": "\d+"
     *     },
     *     defaults={
     *         "serviceId": null
     *     }
     * )
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @ParamConverter("service", options={"id" = "serviceId"})
     */
    public function newAction(Request $request, Client $client, ?Service $service = null): Response
    {
        if ($client->isDeleted()) {
            $this->addTranslatedFlash(
                'danger',
                'Client is archived. All actions are prohibited. You can only restore the client.'
            );

            return new JsonResponse(
                [
                    'redirect' => $this->generateUrl(
                        'client_show',
                        [
                            'id' => $client->getId(),
                        ]
                    ),
                ]
            );
        }

        $servicesForLinkedSubscriptions = $this->get(ServiceDataProvider::class)
            ->getServicesForLinkedSubscriptions($client);
        $linkedSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
            && $client->getOrganization()->hasPaymentProviderSupportingAutopay($this->isSandbox())
            && $servicesForLinkedSubscriptions;

        $customSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && $client->getOrganization()->hasPaymentGateway($this->isSandbox());

        if (! $linkedSubscriptionPossible && ! $customSubscriptionPossible) {
            throw $this->createAccessDeniedException();
        }

        $paymentPlan = new PaymentPlan();
        $paymentPlanFacade = $this->get(PaymentPlanFacade::class);
        $paymentPlanFacade->setDefaults($paymentPlan, $client);
        if (! $customSubscriptionPossible) {
            $paymentPlan->setAmountInSmallestUnit(null);
            $paymentPlan->setPeriod(null);
            $paymentPlan->setService(reset($servicesForLinkedSubscriptions));
        }

        $url = $this->generateUrl(
            'payment_subscription_new',
            [
                'id' => $client->getId(),
                'serviceId' => $service ? $service->getId() : null,
            ]
        );
        $form = $this->createForm(
            PaymentPlanType::class,
            $paymentPlan,
            [
                'action' => $url,
                'client' => $client,
                'smallest_unit_multiplier' => 10 ** $client->getOrganization()->getCurrency()->getFractionDigits(),
            ]
        );
        if ($form->has('service') && $service) {
            $form->get('service')->setData($service);
        }
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
     * @Route("/{id}/cancel", name="payment_subscription_cancel", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cancelAction(PaymentPlan $paymentPlan): Response
    {
        if (
            ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->get(PaymentPlanFacade::class)->cancelSubscription($paymentPlan);
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
            $this->addTranslatedFlash('success', 'Subscription was successfully cancelled.');
        } catch (PaymentPlanException $exception) {
            $this->addTranslatedFlash(
                'danger',
                'An error occurred during subscription cancellation. Reason: %reason%',
                null,
                [
                    '%reason%' => $exception->getMessage(),
                ]
            );
        }

        return $this->redirectToRoute(
            'client_show',
            [
                'id' => $paymentPlan->getClient()->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="payment_subscription_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(PaymentPlan $paymentPlan): Response
    {
        $this->get(PaymentPlanFacade::class)->deleteSubscription($paymentPlan);
        $this->addTranslatedFlash('success', 'Subscription was successfully deleted.');
        $message['logMsg'] = [
            'message' => 'Subscription %s was deleted',
            'replacements' => $paymentPlan->getName(),
        ];

        $this->get(ActionLogger::class)->log(
            $message,
            $this->getUser(),
            $paymentPlan->getClient(),
            EntityLog::PAYMENT_PLAN_DELETED
        );

        return $this->redirectToRoute(
            'client_show',
            [
                'id' => $paymentPlan->getClient()->getId(),
            ]
        );
    }
}
