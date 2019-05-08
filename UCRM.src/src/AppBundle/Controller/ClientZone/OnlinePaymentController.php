<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller\ClientZone;

use AppBundle\Component\AuthorizeNet\AuthorizeNetException;
use AppBundle\Component\AuthorizeNet\AutomatedRecurringBillingFactory;
use AppBundle\Component\AuthorizeNet\CustomerInformationManagerFactory;
use AppBundle\Component\AuthorizeNet\DirectPostMethodFactory;
use AppBundle\Component\IpPay\Exception\FailedPaymentException;
use AppBundle\Component\IpPay\IpPayPaymentHandler;
use AppBundle\Component\MercadoPago;
use AppBundle\Component\PayPal\AgreementState;
use AppBundle\Component\PayPal\ApiContextFactory;
use AppBundle\Component\PayPal\ExpressCheckout;
use AppBundle\Component\PayPal\IPN;
use AppBundle\Component\PayPal\PayPalException;
use AppBundle\Component\PayPal\SubscriptionFactory as PayPalSubscriptionFactory;
use AppBundle\Component\Stripe\ChargeAchFactory;
use AppBundle\Component\Stripe\ChargeFactory;
use AppBundle\Component\Stripe\Exception\StripeEventIgnoredException;
use AppBundle\Component\Stripe\Exception\StripeException;
use AppBundle\Component\Stripe\Exception\StripePaymentIgnoredException;
use AppBundle\Component\Stripe\StripeWebhookHandlerFactory;
use AppBundle\Component\Stripe\SubscriptionFactory;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentPayPal;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentToken;
use AppBundle\Entity\User;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Facade\PaymentTokenFacade;
use AppBundle\Form\Data\Factory\IpPayPaymentDataFactory;
use AppBundle\Form\IpPayPaymentType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\PublicUrlGenerator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use PayPal\Api\Agreement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Stripe\Error;
use Stripe\Error\InvalidRequest;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/online-payment")
 */
class OnlinePaymentController extends BaseController
{
    public const SESSION_NAME_HAS_CLIENT_ZONE = 'online-payment-client-has-client-zone';

    /**
     * @Route("/pay/{token}", name="online_payment_pay")
     * @Method("GET")
     * @Permission("public")
     */
    public function payAction(string $token): Response
    {
        $paymentToken = $this->getPaymentToken($token);
        $invoice = $paymentToken->getInvoice();

        $paymentGateways = [
            'hasPayPal' => null !== $invoice->getOrganization()->getPayPalClientId($this->isSandbox()),
            'hasStripe' => $invoice->getOrganization()->hasStripe($this->isSandbox()),
            'hasStripeAch' => $invoice->getOrganization()->hasStripeAch($this->isSandbox()),
            'hasAnet' => null !== $invoice->getOrganization()->getAnetLoginId($this->isSandbox()),
            'hasIpPay' => null !== $invoice->getOrganization()->getIpPayUrl($this->isSandbox()),
            'hasMercadoPago' => null !== $invoice->getOrganization()->getMercadoPagoClientId(),
        ];

        if (! array_filter($paymentGateways)) {
            return $this->redirectToRoute('client_zone_client_index');
        }

        try {
            $client = $this->getClient();
        } catch (AccessDeniedException $exception) {
            $client = null;
        }

        $verifiedStripeAchAccounts = [];
        if (
            $this->get(AuthorizationChecker::class)->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)
            && $client === $invoice->getClient()
        ) {
            $verifiedStripeAchAccounts = $this->em->getRepository(Client::class)
                ->getStripeAchVerifiedAccounts($invoice->getClient()->getId());
        }

        if ($invoice->getClient()) {
            $this->get(SessionInterface::class)->set(
                self::SESSION_NAME_HAS_CLIENT_ZONE,
                $invoice->getClient()->getUser()->getUsername() && $invoice->getClient()->getUser()->getPassword()
            );
        }

        return $this->render(
            'online_payment/pay.html.twig',
            array_merge(
                $paymentGateways,
                [
                    'invoice' => $invoice,
                    'paymentToken' => $paymentToken,
                    'stripe' => $this->getStripeData($invoice),
                    'paymentGatewaysCount' => count(array_filter($paymentGateways)),
                    'verifiedStripeAchAccounts' => $verifiedStripeAchAccounts,
                    'isAmountChangeEnabled' => $this->getOption(Option::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE),
                ]
            )
        );
    }

    /**
     * @Route("/subscribe/{id}", name="online_payment_subscribe", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     *
     * @throws AccessDeniedException
     */
    public function subscribeAction(PaymentPlan $paymentPlan): Response
    {
        $this->checkSubscribe($paymentPlan, null);
        $client = $paymentPlan->getClient();

        $organization = $paymentPlan->getClient()->getOrganization();
        $amountChangeSupportNeeded = (bool) $paymentPlan->getService();

        $sandbox = $this->isSandbox();
        $paymentGateways = [
            'hasPayPal' => $organization->hasPayPal($sandbox)
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_PAYPAL, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
            'hasStripe' => $organization->hasStripe($sandbox)
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_STRIPE, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
            'hasStripeAch' => $organization->hasStripeAch($sandbox)
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_STRIPE_ACH, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
            'hasAnet' => $organization->hasAuthorizeNet($sandbox)
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_ANET, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
            'hasIpPay' => $organization->hasIpPay($sandbox)
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_IPPAY, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
            'hasMercadoPago' => $organization->hasMercadoPago()
                && (
                    ! $amountChangeSupportNeeded
                    || in_array(PaymentPlan::PROVIDER_MERCADO_PAGO, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
                ),
        ];

        $verifiedStripeAchAccounts = $this->em->getRepository(Client::class)->getStripeAchVerifiedAccounts(
            $client->getId()
        );

        return $this->render(
            'online_payment/subscribe.html.twig',
            array_merge(
                $paymentGateways,
                [
                    'paymentPlan' => $paymentPlan,
                    'client' => $client,
                    'organization' => $organization,
                    'stripe' => $this->getStripeDataPaymentPlan($paymentPlan),
                    'onlyOnePaymentGateway' => count(array_filter($paymentGateways)) + count(
                            $verifiedStripeAchAccounts
                        ) === 1,
                    'verifiedStripeAchAccounts' => $verifiedStripeAchAccounts,
                ]
            )
        );
    }

    /**
     * @Route("/success", name="online_payment_success")
     * @Method("GET")
     * @Permission("public")
     */
    public function successAction(): Response
    {
        return $this->render(
            'online_payment/success.html.twig',
            [
                'clientHasAccessToClientZone' => $this->get(SessionInterface::class)->get(
                    self::SESSION_NAME_HAS_CLIENT_ZONE,
                    false
                ),
            ]
        );
    }

    /**
     * @Route("/pending", name="online_payment_pending")
     * @Method("GET")
     * @Permission("public")
     */
    public function pendingAction(): Response
    {
        return $this->render(
            'online_payment/pending.html.twig'
        );
    }

    /**
     * @Route("/cancelled/{token}", name="online_payment_cancelled")
     * @Method("GET")
     * @Permission("public")
     */
    public function cancelledAction(Request $request, string $token): Response
    {
        $paymentToken = $this->getPaymentToken($token);
        $invoice = $paymentToken->getInvoice();

        return $this->render(
            'online_payment/cancelled.html.twig',
            [
                'invoice' => $invoice,
                'paymentToken' => $paymentToken,
                'message' => $request->get('message'),
            ]
        );
    }

    /**
     * @Route("/paypal/{token}", name="paypal_index")
     * @Method("GET")
     * @Permission("public")
     *
     * @throws NotFoundHttpException
     * @throws PayPalException
     */
    public function payPalIndexAction(Request $request, string $token): RedirectResponse
    {
        $amount = $request->get('amount');

        $paymentToken = $this->getPaymentToken($token);

        if ($response = $this->validateRequestedAmount($paymentToken, $amount)) {
            return $response;
        }

        $invoice = $paymentToken->getInvoice();

        try {
            $returnUrl = $this->container->get(PublicUrlGenerator::class)->generate(
                'paypal_success',
                ['token' => $token],
                true
            );
            $cancelUrl = $this->container->get(PublicUrlGenerator::class)->generate(
                'paypal_cancel',
                ['token' => $token],
                true
            );
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'online_payment_pay',
                [
                    'token' => $token,
                ]
            );
        }

        $organization = $invoice->getOrganization();

        $apiContext = $this->get(ApiContextFactory::class)->create($organization, $this->isSandbox());

        $amount = (float) $amount;
        $invoiceAmount = min($amount, round($invoice->getAmountToPay(), 2));

        $expressCheckout = new ExpressCheckout($apiContext);
        $expressCheckout->setCurrency($invoice->getCurrency()->getCode());
        $expressCheckout->setPaymentType(ExpressCheckout::PAYPAL);
        $expressCheckout->addItem(
            $this->trans('Invoice') . ' ' . $invoice->getInvoiceNumber(),
            $invoice->getInvoiceNumber(),
            1,
            $invoiceAmount,
            0
        );

        if ($invoiceAmount < $amount) {
            $expressCheckout->addItem(
                $this->trans('Credit'),
                'Credit',
                1,
                $amount - $invoiceAmount,
                0
            );
        }

        $expressCheckout->setReturnUrl($returnUrl);
        $expressCheckout->setCancelUrl($cancelUrl);
        try {
            $payment = $expressCheckout->createPayment();
        } catch (PayPalException $exception) {
            return $this->logPayPalError($exception->getMessage(), $exception->getErrorData(), $paymentToken);
        }

        $paymentToken->setAmount($amount);

        $this->em->flush($paymentToken);

        return $this->redirect($payment->getApprovalLink());
    }

    /**
     * @Route("/paypal/success/{token}", name="paypal_success")
     * @Method("GET")
     * @Permission("public")
     *
     * @throws \Exception
     */
    public function payPalPaymentSuccessAction(Request $request, string $token): RedirectResponse
    {
        $paymentToken = $this->getPaymentToken($token);
        $invoice = $paymentToken->getInvoice();
        $client = $invoice->getClient();
        $organization = $invoice->getOrganization();
        $amount = round($paymentToken->getAmount(), 2);
        $subtotal = $amount;
        $tax = 0.0;

        $apiContext = $this->get(ApiContextFactory::class)->create($organization, $this->isSandbox());
        $expressCheckout = new ExpressCheckout($apiContext);

        try {
            $apiPayment = $expressCheckout
                ->setCurrency($invoice->getCurrency()->getCode())
                ->setPaymentType(ExpressCheckout::PAYPAL)
                ->processPayment($request, $amount, $subtotal, $tax);
        } catch (PayPalException $exception) {
            return $this->logPayPalError($exception->getMessage(), $exception->getErrorData(), $paymentToken);
        }

        $result = $expressCheckout->getResultPayment();

        $paymentPayPal = new PaymentPayPal();
        $paymentPayPal->setPayPalId($apiPayment->getId());
        $paymentPayPal->setIntent($apiPayment->getIntent());
        $paymentPayPal->setState($apiPayment->getState());
        $paymentPayPal->setOrganization($organization);
        $paymentPayPal->setClient($client);
        $paymentPayPal->setType(PaymentPayPal::TYPE_PAYMENT);
        $paymentPayPal->setAmount($amount);

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_PAYPAL);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($amount);
        $payment->setNote($result->getId());
        $payment->setClient($client);
        $payment->setCurrency($invoice->getCurrency());

        $this->get(PaymentFacade::class)->handleCreateOnlinePayment($payment, $paymentPayPal, $paymentToken);

        return $this->redirectToRoute('online_payment_success');
    }

    /**
     * @Route("/paypal/cancel/{token}", name="paypal_cancel")
     * @Method("GET")
     * @Permission("public")
     */
    public function payPalPaymentCancelAction(string $token): RedirectResponse
    {
        return $this->redirectToRoute('online_payment_cancelled', ['token' => $token]);
    }

    /**
     * @Route("/stripe-success/{token}", name="stripe_success")
     * @Method("POST")
     * @Permission("public")
     */
    public function stripeSuccessAction(Request $request, string $token): RedirectResponse
    {
        $paymentToken = $this->getPaymentToken($token);

        $amountInSmallestUnit = $request->request->get('amount');
        if (! is_numeric($amountInSmallestUnit)) {
            $this->addTranslatedFlash('error', 'Amount is not numeric.');

            return $this->redirectToRoute('online_payment_pay', ['token' => $token]);
        }

        try {
            $this->get(ChargeFactory::class)
                ->create($this->isSandbox())
                ->process($request, $paymentToken, (int) $amountInSmallestUnit);
        } catch (Error\Base $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute('online_payment_pay', ['token' => $token]);
        }

        return $this->redirectToRoute(
            'online_payment_success'
        );
    }

    /**
     * @Route("/stripe-ach/{token}/{bankAccount}", name="stripe_ach", requirements={"bankAccount": "\d+"})
     * @Method("POST")
     * @Permission("guest")
     */
    public function stripeAchAction(Request $request, string $token, ClientBankAccount $bankAccount): RedirectResponse
    {
        $paymentToken = $this->getPaymentToken($token);

        if ($paymentToken->getPaymentStripePending()) {
            $this->addTranslatedFlash(
                'error',
                'This invoice has pending ACH payment assigned to it, it\'s not possible to use different payment method now.'
            );

            return $this->redirectToRoute('online_payment_pay', ['token' => $token]);
        }

        try {
            $this->get(ChargeAchFactory::class)
                ->create($this->isSandbox())
                ->process($request, $paymentToken, $bankAccount);
        } catch (Error\Base $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute('online_payment_pay', ['token' => $token]);
        }

        return $this->redirectToRoute(
            'online_payment_success'
        );
    }

    /**
     * @Route("/stripe-subscribe-success", name="stripe_subscribe_success")
     * @Method("POST")
     * @Permission("guest")
     *
     * @throws InvalidRequest
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function stripeSubscribeSuccessAction(Request $request): RedirectResponse
    {
        if (
            ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw $this->createAccessDeniedException();
        }

        $paymentPlan = $this->em->find(PaymentPlan::class, (int) $request->request->get('paymentPlanId'));
        if (! $paymentPlan) {
            throw $this->createNotFoundException();
        }

        if ($paymentPlan->getProvider() !== null) {
            $this->addTranslatedFlash(
                'error',
                'Subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $stripeToken = $request->request->get('stripeToken');
        if (! $stripeToken) {
            $this->addTranslatedFlash('error', 'Stripe token is not present in the request.');

            return $this->redirectToRoute('online_payment_subscribe', ['id' => $paymentPlan->getId()]);
        }

        $paymentPlan->setProvider(PaymentPlan::PROVIDER_STRIPE);
        $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
        $paymentPlan->generateProviderPlanId();
        $this->em->flush();

        $subscription = $this->get(SubscriptionFactory::class)->create(
            $paymentPlan,
            $stripeToken,
            $this->isSandbox(),
            $request->request->get('stripeEmail')
        );
        try {
            $subscription->execute();
        } catch (Error\Base | StripeException $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute('online_payment_subscribe', ['id' => $paymentPlan->getId()]);
        }

        return $this->redirectToRoute(
            'online_subscription_successful',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/stripe-ach-subscribe/{paymentPlan}/{bankAccount}", name="stripe_ach_subscribe", requirements={"bankAccount": "\d+"})
     * @Method("POST")
     * @Permission("guest")
     *
     * @throws InvalidRequest
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function stripeAchSubscribeAction(
        PaymentPlan $paymentPlan,
        ClientBankAccount $bankAccount
    ): RedirectResponse {
        if (
            ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw $this->createAccessDeniedException();
        }

        if ($paymentPlan->getProvider() !== null) {
            $this->addTranslatedFlash(
                'error',
                'Subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $paymentPlan->setProvider(PaymentPlan::PROVIDER_STRIPE_ACH);
        $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
        $paymentPlan->generateProviderPlanId();
        $this->em->flush();

        $subscription = $this->get(SubscriptionFactory::class)->createAch(
            $paymentPlan,
            $bankAccount,
            $this->isSandbox()
        );

        try {
            $subscription->execute();
        } catch (Error\Base | StripeException $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute('online_payment_subscribe', ['id' => $paymentPlan->getId()]);
        }

        return $this->redirectToRoute(
            'online_subscription_successful',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/stripe-webhook/{id}", name="stripe_webhook", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("public")
     */
    public function stripeWebhookAction(Request $request): Response
    {
        $organization = $this->em->find(Organization::class, (int) $request->get('id'));
        if (! $organization) {
            return new Response('Organization not found.', Response::HTTP_NOT_FOUND);
        }

        if ($request->getMethod() === 'GET') {
            return $this->createWebhookMethodNotAllowedResponse();
        }

        try {
            $this->get(StripeWebhookHandlerFactory::class)
                ->create($organization, $this->isSandbox())
                ->process($request);
        } catch (StripeException $exception) {
            return new Response(
                $exception->getMessage(),
                $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (Error\Base $exception) {
            return new Response(
                $exception->getMessage(),
                $exception->getHttpStatus() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (AccessDeniedException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        } catch (StripePaymentIgnoredException $exception) {
            $this->get('logger')->addError($exception->getMessage());

            return new Response($exception->getMessage(), Response::HTTP_OK);
        } catch (StripeEventIgnoredException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return new Response(
                sprintf('"%s": "%s"', get_class($exception), $exception->getMessage()),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @Route("/subscribe-paypal/{id}", name="online_payment_paypal_subscribe", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     *
     * @throws OptimisticLockException
     */
    public function payPalSubscribeAction(PaymentPlan $paymentPlan): RedirectResponse
    {
        $this->checkSubscribe($paymentPlan, PaymentPlan::PROVIDER_PAYPAL);

        if ($paymentPlan->getProvider() !== null) {
            $this->addTranslatedFlash(
                'error',
                'Subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $client = $paymentPlan->getClient();
        if (
            ! $client->getStreet1()
            || ! $client->getCity()
            || ! $client->getCountry()
            || ! $client->getZipCode()
        ) {
            $this->addTranslatedFlash(
                'error',
                'Client\'s address is required by PayPal to create subscriptions.'
            );

            return $this->redirectToRoute(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $paymentPlan->setProvider(PaymentPlan::PROVIDER_PAYPAL);
        $this->em->flush();

        $publicUrlGenerator = $this->get(PublicUrlGenerator::class);
        try {
            $returnUrl = $publicUrlGenerator->generate(
                'online_paypal_subscription_return',
                [
                    'id' => $paymentPlan->getId(),
                ],
                true
            );
            $cancelUrl = $publicUrlGenerator->generate(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ],
                true
            );
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $subscription = $this->get(PayPalSubscriptionFactory::class)->create(
            $paymentPlan,
            $returnUrl,
            $cancelUrl,
            $this->isSandbox()
        );

        try {
            $approvalLink = $subscription->execute();

            if ($approvalLink) {
                return $this->redirect($approvalLink);
            }
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute(
            'online_subscription_failed',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/subscribe-paypal-return/{id}", name="online_paypal_subscription_return", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function payPalSubscribeReturnAction(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        $token = $request->get('token');
        $agreement = new Agreement();
        try {
            $apiContext = $this->get(ApiContextFactory::class)->create(
                $paymentPlan->getClient()->getOrganization(),
                $this->isSandbox()
            );
            $agreement->execute($token, $apiContext);

            if (! in_array($agreement->state, [AgreementState::ACTIVE, AgreementState::PENDING], true)) {
                throw new \Exception(
                    sprintf(
                        'Unexpected agreement state: "%s"',
                        $agreement->state
                    )
                );
            }

            $paymentPlan->setProviderSubscriptionId($agreement->getId());
            $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
            $paymentPlan->setActive(true);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        return $this->redirectToRoute(
            'online_subscription_successful',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/subscription-failed/{id}", name="online_subscription_failed", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function subscriptionFailedAction(PaymentPlan $paymentPlan): Response
    {
        return $this->render(
            'online_payment/subscription_failed.html.twig',
            [
                'paymentPlan' => $paymentPlan,
            ]
        );
    }

    /**
     * @Route("/subscription-successful/{id}", name="online_subscription_successful", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function subscriptionSuccessfulAction(PaymentPlan $paymentPlan): Response
    {
        return $this->render(
            'online_payment/subscription_successful.html.twig',
            [
                'paymentPlan' => $paymentPlan,
            ]
        );
    }

    /**
     * @Route("/paypal-ipn/{id}", name="paypal_ipn", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("public")
     */
    public function paypalIpnAction(Request $request): Response
    {
        $organization = $this->em->find(Organization::class, (int) $request->get('id'));
        if (! $organization) {
            return new Response('Organization not found.', 404);
        }

        if ($request->getMethod() === 'GET') {
            return $this->createWebhookMethodNotAllowedResponse();
        }

        $listener = $this->get(IPN::class)
            ->setSandbox($this->isSandbox());
        $data = $listener->getVerifiedRequest();
        if (false === $data) {
            return new Response('', 400);
        }

        $code = $listener->processVerifiedRequest($data);

        return new Response('', $code);
    }

    /**
     * @Route("/anet/{token}", name="anet_index")
     * @Method("GET")
     * @Permission("public")
     */
    public function anetIndexAction(Request $request, string $token): Response
    {
        $amount = $request->get('amount');

        $paymentToken = $this->getPaymentToken($token);

        if ($response = $this->validateRequestedAmount($paymentToken, $amount)) {
            return $response;
        }

        $invoice = $paymentToken->getInvoice();
        $organization = $invoice->getOrganization();

        $paymentToken->setAmount((float) $amount);

        $this->em->flush($paymentToken);

        $dpm = $this->get(DirectPostMethodFactory::class)->create(
            $organization,
            $this->isSandbox(),
            $token
        );

        try {
            $form = $dpm->getPaymentForm($invoice, $amount);
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'online_payment_pay',
                [
                    'token' => $token,
                ]
            );
        }

        return $this->render(
            'online_payment/anet/pay.html.twig',
            [
                'form' => $form,
                'invoice' => $invoice,
            ]
        );
    }

    /**
     * @Route("/anet/relay/{token}", name="anet_relay")
     * @Method("POST")
     * @Permission("public")
     */
    public function anetRelayAction(string $token): Response
    {
        $paymentToken = $this->getPaymentToken($token);
        $organization = $paymentToken->getInvoice()->getOrganization();

        $dpm = $this->get(DirectPostMethodFactory::class)->create(
            $organization,
            $this->isSandbox(),
            $token
        );

        return new Response($dpm->processRelay($paymentToken));
    }

    /**
     * @Route("/subscribe-anet/{id}", name="anet_subscribe", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function anetSubscribeAction(Request $request, PaymentPlan $paymentPlan): Response
    {
        $this->checkSubscribe($paymentPlan, PaymentPlan::PROVIDER_ANET);
        $client = $paymentPlan->getClient();

        if ($paymentPlan->getProvider() && $paymentPlan->getProvider() !== PaymentPlan::PROVIDER_ANET) {
            $this->addTranslatedFlash(
                'error',
                'Different subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        if (! $paymentPlan->getProvider()) {
            $paymentPlan->setProvider(PaymentPlan::PROVIDER_ANET);
            $this->em->flush($paymentPlan);
        }

        $cim = $this->get(CustomerInformationManagerFactory::class)->create(
            $client->getOrganization(),
            $this->isSandbox()
        );

        try {
            $cim->verifyCustomerProfileId($client);
            $cim->updatePaymentProfile($client);
            $hostedFormParameters = $cim->getHostedFormParameters(
                $client,
                $paymentPlan
            );
        } catch (AuthorizeNetException | PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        return $this->render(
            'online_payment/anet/customer_profile.html.twig',
            [
                'organization' => $client->getOrganization(),
                'hostedFormParameters' => $hostedFormParameters,
                'noRedirect' => $request->get('no-redirect', false),
            ]
        );
    }

    /**
     * @Route("/subscribe-anet-profile-return/{id}", name="anet_subscribe_profile_return", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     *
     * @throws AccessDeniedException
     */
    public function anetSubscribeProfileReturnAction(PaymentPlan $paymentPlan): RedirectResponse
    {
        $this->checkSubscribe($paymentPlan, PaymentPlan::PROVIDER_ANET);
        $client = $paymentPlan->getClient();
        $organization = $client->getOrganization();
        $isSandbox = $this->isSandbox();

        $cim = $this->get(CustomerInformationManagerFactory::class)->create($organization, $isSandbox);
        $cim->updatePaymentProfile($client);

        if (! $client->getAnetCustomerPaymentProfileId()) {
            $this->addTranslatedFlash('error', 'You have to fill your payment details for subscription.');

            return $this->redirectToRoute(
                'anet_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                    'no-redirect' => true,
                ]
            );
        }

        $arb = $this->get(AutomatedRecurringBillingFactory::class)->create($organization, $isSandbox);

        try {
            $arb->createSubscription($client, $paymentPlan);
        } catch (AuthorizeNetException $e) {
            $this->addTranslatedFlash('error', $e->getMessage());

            return $this->redirectToRoute(
                'online_subscription_failed',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        return $this->redirectToRoute(
            'online_subscription_successful',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/ippay/{token}", name="ippay_index")
     * @Method({"GET", "POST"})
     * @Permission("public")
     *
     * @throws NotFoundHttpException
     */
    public function ipPayAction(Request $request, string $token): Response
    {
        $amount = $request->get('amount');

        $paymentToken = $this->getPaymentToken($token);

        if ($response = $this->validateRequestedAmount($paymentToken, $amount)) {
            return $response;
        }

        $invoice = $paymentToken->getInvoice();

        if ($invoice->getCurrency() !== $invoice->getOrganization()->getIpPayMerchantCurrency($this->isSandbox())) {
            $this->addTranslatedFlash('error', 'The IPPay Merchant currency does not match your invoice currency.');

            return $this->redirectToRoute(
                'online_payment_pay',
                [
                    'token' => $token,
                ]
            );
        }

        $paymentToken->setAmount((float) $amount);

        $this->em->flush($paymentToken);

        $ipPayPayment = $this->get(IpPayPaymentDataFactory::class)->createFromInvoice($invoice);

        $form = $this->createForm(IpPayPaymentType::class, $ipPayPayment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get(IpPayPaymentHandler::class)->processPayment($form->getData(), $paymentToken);
            } catch (FailedPaymentException $e) {
                $this->addTranslatedFlash(
                    'error',
                    $e->getMessage(),
                    null,
                    $e->getErrorCode() ? ['%code%' => $e->getErrorCode()] : []
                );

                if ($e->getErrorMessage()) {
                    $this->addFlash('error', $e->getErrorMessage());
                }

                return $this->redirectToRoute(
                    'online_payment_pay',
                    [
                        'token' => $token,
                    ]
                );
            }

            return $this->redirectToRoute('online_payment_success');
        }

        return $this->render(
            'online_payment/ippay/pay.html.twig',
            [
                'form' => $form->createView(),
                'invoice' => $invoice,
            ]
        );
    }

    /**
     * @Route("/subscribe-ippay/{id}", name="ippay_subscribe", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function ipPaySubscribeAction(Request $request, PaymentPlan $paymentPlan): Response
    {
        $this->checkSubscribe($paymentPlan, PaymentPlan::PROVIDER_IPPAY);
        $client = $paymentPlan->getClient();

        if ($paymentPlan->getProvider() && $paymentPlan->getProvider() !== PaymentPlan::PROVIDER_IPPAY) {
            $this->addTranslatedFlash(
                'error',
                'Different subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        if ($paymentPlan->isActive() || $paymentPlan->getNextPaymentDate()) {
            $this->addTranslatedFlash(
                'error',
                'Subscription is already active.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        if (! $paymentPlan->getProvider()) {
            $paymentPlan->setProvider(PaymentPlan::PROVIDER_IPPAY);
            $this->em->flush($paymentPlan);
        }

        $ipPayPayment = $this->get(IpPayPaymentDataFactory::class)->createFromClient($client);

        $form = $this->createForm(IpPayPaymentType::class, $ipPayPayment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get(IpPayPaymentHandler::class)->processSubscription($form->getData(), $paymentPlan);
            } catch (FailedPaymentException $e) {
                $this->addTranslatedFlash(
                    'error',
                    $e->getMessage(),
                    null,
                    $e->getErrorCode() ? ['%code%' => $e->getErrorCode()] : []
                );

                if ($e->getErrorMessage()) {
                    $this->addFlash('error', $e->getErrorMessage());
                }

                return $this->redirectToRoute(
                    'online_subscription_failed',
                    [
                        'id' => $paymentPlan->getId(),
                    ]
                );
            }

            return $this->redirectToRoute(
                'online_subscription_successful',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        return $this->render(
            'online_payment/ippay/subscribe.html.twig',
            [
                'form' => $form->createView(),
                'organization' => $client->getOrganization(),
            ]
        );
    }

    /**
     * @Route("/mercado-pago/{token}", name="mercado_pago_index")
     * @Method("GET")
     * @Permission("public")
     *
     * @throws NotFoundHttpException
     */
    public function mercadoPagoIndexAction(Request $request, string $token): Response
    {
        $amount = $request->get('amount');

        $paymentToken = $this->getPaymentToken($token);

        if ($response = $this->validateRequestedAmount($paymentToken, $amount)) {
            return $response;
        }

        $paymentToken->setAmount((float) $amount);
        $this->get(PaymentTokenFacade::class)->handleEdit($paymentToken);

        try {
            $initPoint = $this->get(MercadoPago\InitPointRequester::class)
                ->requestOneTimePaymentInitPoint($paymentToken);
        } catch (\MercadoPagoException | PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'online_payment_pay',
                [
                    'token' => $token,
                ]
            );
        }

        return $this->redirect($initPoint);
    }

    /**
     * @Route("/mercado-pago-ipn/{organizationId}", name="mercado_pago_ipn", requirements={"organizationId": "\d+"})
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"id" = "organizationId"})
     * @Permission("public")
     */
    public function mercadoPagoIpnAction(Request $request, Organization $organization): Response
    {
        try {
            $this->get(MercadoPago\NotificationsHandlerFactory::class)->create($organization)->handleRequest($request);
        } catch (\MercadoPagoException $exception) {
            $this->get('logger')->addError(
                Json::encode(
                    [
                        'exception' => [
                            'message' => $exception->getMessage(),
                            'code' => $exception->getCode(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'trace' => $exception->getTrace(),
                        ],
                    ]
                )
            );

            // As there is no way to know if the request is actually webhook test (long live MercadoPago docs)
            // we need to always return 200 OK.
            // We've had problems, where user was not able to setup webhook URL, because we returned
            // something else than 200.
            return new Response($exception->getMessage(), 200);
        } catch (UniqueConstraintViolationException $exception) {
            $this->get('logger')->addError(
                Json::encode(
                    [
                        'message' => 'MercadoPago payment ignored, already exists.',
                        'request' => $request->query->all(),
                    ]
                )
            );
        }

        return new Response();
    }

    /**
     * @Route(
     *     "/mercado-pago-subscription-return/{paymentPlanId}",
     *     name="mercado_pago_subscription_return",
     *     requirements={"paymentPlanId": "\d+"}
     * )
     * @Method({"GET", "POST"})
     * @Permission("guest")
     * @ParamConverter("paymentPlan", options={"id" = "paymentPlanId"})
     */
    public function mercadoPagoSubscriptionReturnAction(PaymentPlan $paymentPlan): Response
    {
        return $this->redirectToRoute(
            'online_subscription_successful',
            [
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Route("/subscribe-mercado-pago/{id}", name="mercado_pago_subscribe", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function mercadoPagoSubscriptionAction(PaymentPlan $paymentPlan): Response
    {
        $this->checkSubscribe($paymentPlan, PaymentPlan::PROVIDER_MERCADO_PAGO);

        if ($paymentPlan->getProvider() !== null) {
            $this->addTranslatedFlash(
                'error',
                'Subscription method is already selected. Please continue in existing payment process or create new subscription.'
            );

            return $this->redirectToRoute(
                'online_payment_subscribe',
                [
                    'id' => $paymentPlan->getId(),
                ]
            );
        }

        $paymentPlan->setProvider(PaymentPlan::PROVIDER_MERCADO_PAGO);
        $this->em->flush();

        try {
            $initPoint = $this->get(MercadoPago\InitPointRequester::class)->requestSubscriptionInitPoint($paymentPlan);
        } catch (\MercadoPagoException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('online_payment_subscribe', ['id' => $paymentPlan->getId()]);
        }

        return $this->redirect($initPoint);
    }

    private function createWebhookMethodNotAllowedResponse(): Response
    {
        $response = new Response('', Response::HTTP_METHOD_NOT_ALLOWED);

        return $this->render(
            'online_payment/webhook/405.html.twig',
            [],
            $response
        );
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getPaymentToken(string $token): PaymentToken
    {
        $repository = $this->em->getRepository(PaymentToken::class);
        $paymentToken = $repository->findOneBy(
            [
                'token' => $token,
            ]
        );

        if (! $paymentToken) {
            throw $this->createNotFoundException();
        }

        return $paymentToken;
    }

    private function getStripeData(Invoice $invoice): array
    {
        Stripe::setApiKey($invoice->getOrganization()->getStripeSecretKey($this->isSandbox()));

        return [
            'currency' => $invoice->getCurrency()->getCode(),
            'description' => $this->trans('Invoice') . ' ' . $invoice->getInvoiceNumber(),
            'publishableKey' => $invoice->getOrganization()->getStripePublishableKey($this->isSandbox()),
            'email' => $invoice->getClient()->getFirstBillingEmail() ?? '',
            'currencyMultiplier' => 10 ** $invoice->getCurrency()->getFractionDigits(),
        ];
    }

    private function getStripeDataPaymentPlan(PaymentPlan $paymentPlan): array
    {
        $organization = $paymentPlan->getClient()->getOrganization();
        Stripe::setApiKey($organization->getStripeSecretKey($this->isSandbox()));

        return [
            'amount' => $paymentPlan->getAmountInSmallestUnit(),
            'currency' => $paymentPlan->getCurrency()->getCode(),
            'publishableKey' => $organization->getStripePublishableKey($this->isSandbox()),
            'email' => $paymentPlan->getClient()->getFirstBillingEmail() ?: '',
            'currencyMultiplier' => $paymentPlan->getSmallestUnitMultiplier(),
        ];
    }

    private function logPayPalError(
        string $errorMessage,
        array $errorData,
        PaymentToken $paymentToken
    ): RedirectResponse {
        $error = sprintf(
            implode(
                PHP_EOL,
                [
                    'Error with processing payment with PayPal.',
                    'PayPal status:',
                    '',
                    '%s',
                    '',
                    'error data:',
                    '',
                    'name: %s',
                    'message: %s',
                    'information link: %s',
                    'debug ID: %s',
                    'details: %s',
                ]
            ),
            $errorMessage,
            $errorData['name'] ?? '',
            $errorData['message'] ?? '',
            $errorData['information_link'] ?? '',
            $errorData['debug_id'] ?? '',
            isset($errorData['details']) ? Json::encode($errorData['details']) : ''
        );

        $message['logMsg'] = [
            'message' => $error,
            'replacements' => '',
        ];
        $logger = $this->container->get(ActionLogger::class);
        $logger->log(
            $message,
            $this->getUser(),
            $paymentToken->getInvoice()->getClient(),
            EntityLog::ONLINE_PAYMENT_FAILURE
        );

        $this->addTranslatedFlash('error', $error);

        return $this->redirect(
            $this->generateUrl(
                'paypal_cancel',
                [
                    'token' => $paymentToken->getToken(),
                ]
            )
        );
    }

    private function checkSubscribe(PaymentPlan $paymentPlan, ?string $provider): void
    {
        if (
            ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isGranted(User::ROLE_ADMIN)) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, \AppBundle\Controller\PaymentController::class);
        } elseif ($paymentPlan->getClient() !== $this->getClient()) {
            throw $this->createAccessDeniedException();
        }

        // If subscribing to autopay payment plan (linked service), check if provider supports it.
        if (
            $provider !== null
            && $paymentPlan->isLinked()
            && ! in_array($provider, PaymentPlan::PROVIDER_SUPPORTED_AUTOPAY, true)
        ) {
            throw $this->createAccessDeniedException();
        }
    }

    private function validateRequestedAmount(PaymentToken $paymentToken, ?string $amount): ?RedirectResponse
    {
        $fractionDigits = $paymentToken->getInvoice()->getCurrency()->getFractionDigits();
        $amountToPay = $paymentToken->getInvoice()->getAmountToPay();

        if (
            $amount === null
            || $amount === '0'
            || ! Strings::match($amount, '/^\d++(\.\d++)?$/')
            || (
                ! $this->getOption(Option::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE)
                && round($amount, $fractionDigits) !== round($amountToPay, $fractionDigits)
            )
        ) {
            return $this->redirect(
                $this->generateUrl(
                    'online_payment_pay',
                    [
                        'token' => $paymentToken->getToken(),
                    ]
                )
            );
        }

        return null;
    }
}
