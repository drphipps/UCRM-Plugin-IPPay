<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\AuthorizeNet\AuthorizeNetAPIAccess;
use AppBundle\Component\AuthorizeNet\AuthorizeNetException;
use AppBundle\Component\AuthorizeNet\AutomatedRecurringBillingFactory;
use AppBundle\Component\MercadoPago\MercadoPagoFactory;
use AppBundle\Component\PayPal\ApiContextFactory;
use AppBundle\Component\Stripe\Customer;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\General;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Event\PaymentPlan\PaymentPlanDeleteEvent;
use AppBundle\Event\PaymentPlan\PaymentPlanEditEvent;
use AppBundle\Exception\PaymentPlanException;
use AppBundle\RabbitMq\PaymentPlan\CancelPaymentPlanMessage;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceCalculations;
use AppBundle\Util\Formatter;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManager;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use Psr\Log\LoggerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use Stripe\Error;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionDispatcher;

class PaymentPlanFacade
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiContextFactory
     */
    private $apiContextFactory;

    /**
     * @var AutomatedRecurringBillingFactory
     */
    private $anetFactory;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var MercadoPagoFactory
     */
    private $mercadoPagoFactory;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var Customer
     */
    private $stripeCustomer;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        LoggerInterface $logger,
        EntityManager $entityManager,
        Options $options,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        \Twig_Environment $twig,
        ApiContextFactory $apiContextFactory,
        AutomatedRecurringBillingFactory $anetFactory,
        ServiceCalculations $serviceCalculations,
        TranslatorInterface $translator,
        Formatter $formatter,
        NotificationFactory $notificationFactory,
        MercadoPagoFactory $mercadoPagoFactory,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        Customer $stripeCustomer,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->twig = $twig;
        $this->apiContextFactory = $apiContextFactory;
        $this->anetFactory = $anetFactory;
        $this->serviceCalculations = $serviceCalculations;
        $this->translator = $translator;
        $this->formatter = $formatter;
        $this->notificationFactory = $notificationFactory;
        $this->mercadoPagoFactory = $mercadoPagoFactory;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->stripeCustomer = $stripeCustomer;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function cancelSubscription(PaymentPlan $paymentPlan, bool $sendNotification = true): void
    {
        if (! $paymentPlan->isActive()) {
            return;
        }

        $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $organization = $paymentPlan->getClient()->getOrganization();

        try {
            switch ($paymentPlan->getProvider()) {
                case PaymentPlan::PROVIDER_STRIPE:
                case PaymentPlan::PROVIDER_STRIPE_ACH:
                    Stripe::setApiKey($organization->getStripeSecretKey($isSandbox));

                    $sub = Subscription::retrieve($paymentPlan->getProviderSubscriptionId());
                    $sub->cancel();

                    $plan = Plan::retrieve($paymentPlan->getProviderPlanId());
                    $plan->delete();

                    break;
                case PaymentPlan::PROVIDER_PAYPAL:
                    $apiContext = $this->apiContextFactory->create($organization, $isSandbox);

                    $agreementStateDescriptor = new AgreementStateDescriptor();
                    $agreementStateDescriptor->setNote('Subscription cancel');

                    $agreement = Agreement::get($paymentPlan->getProviderSubscriptionId(), $apiContext);
                    // no need to crash, when the agreement is already cancelled
                    if ($agreement->getState() !== 'Cancelled') {
                        $agreement->cancel($agreementStateDescriptor, $apiContext);
                    }

                    break;
                case PaymentPlan::PROVIDER_ANET:
                    try {
                        $this->anetFactory->create($organization, $isSandbox)->cancelSubscription(
                            $paymentPlan->getProviderSubscriptionId()
                        );
                    } catch (AuthorizeNetException $exception) {
                        $alreadyCancelled = false;
                        foreach ($exception->getMessages() as $message) {
                            if ($message->getCode() === AuthorizeNetAPIAccess::ERROR_TERMINATED) {
                                $alreadyCancelled = true;
                            }
                        }

                        if (! $alreadyCancelled) {
                            throw $exception;
                        }
                    }

                    break;
                case PaymentPlan::PROVIDER_MERCADO_PAGO:
                    $mp = $this->mercadoPagoFactory->create($organization);
                    $mp->cancel_preapproval_payment($paymentPlan->getProviderPlanId());

                    break;
                case PaymentPlan::PROVIDER_IPPAY:
                    // Nothing needed.
                    break;
                default:
                    throw new PaymentPlanException('Payment plan provider is not found.');
            }
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Can not cancel subscription. Reason: %s', $exception->getMessage()));
            $paymentPlan->setCancellationFailed(true);
            $this->entityManager->flush($paymentPlan);

            throw new PaymentPlanException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->deleteSubscription($paymentPlan, $sendNotification);
    }

    public function deleteSubscription(PaymentPlan $paymentPlan, bool $sendNotification = true): void
    {
        if (! $paymentPlan->isActive()) {
            return;
        }

        $paymentPlanId = $paymentPlan->getId();
        $paymentPlan->setActive(false);
        $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
        $paymentPlan->setCanceledDate(new \DateTime());
        $paymentPlan->setService(null);
        if (! $this->entityManager->getUnitOfWork()->isScheduledForDelete($paymentPlan)) {
            $this->entityManager->flush($paymentPlan);
        }

        if ($this->options->get(Option::NOTIFICATION_SUBSCRIPTION_CANCELLED) && $sendNotification) {
            $this->sendSubscriptionCancelledEmail($paymentPlan);
        }

        $this->transactionDispatcher->transactional(
            function () use ($paymentPlan, $paymentPlanId) {
                yield new PaymentPlanDeleteEvent($paymentPlan, $paymentPlanId);
            }
        );

        $this->deleteAllCustomerDataWhenNotNeededAnymore($paymentPlan->getClient());
    }

    public function unsubscribeMultiple(array $paymentPlans = []): void
    {
        foreach ($paymentPlans as $paymentPlan) {
            if (! $paymentPlan->isActive()) {
                continue;
            }

            $this->rabbitMqEnqueuer->enqueue(new CancelPaymentPlanMessage($paymentPlan));
        }
    }

    public function setDefaults(PaymentPlan $paymentPlan, Client $client): void
    {
        $paymentPlan->setClient($client);
        $client->addPaymentPlan($paymentPlan);
        $paymentPlan->setCurrency($client->getOrganization()->getCurrency());

        $fractionDigits = $client->getOrganization()->getCurrency()->getFractionDigits() ?? 2;

        $paymentPlan->setSmallestUnitMultiplier(10 ** $fractionDigits);

        // Set default period and amount based on already active subscriptions and client's services
        $activePlans = $client->getActivePaymentPlans();
        $defaultAmount = 0.0;
        $alreadySubscribedAmount = 0;
        foreach ($activePlans as $plan) {
            $alreadySubscribedAmount += $plan->getAmountInSmallestUnit();
        }

        $services = $client->getNotDeletedServices();
        $period = null;
        foreach ($services as $service) {
            $period = $period ?: $service->getTariffPeriodMonths();
            if ($service->getTariffPeriodMonths() === $period) {
                $defaultAmount += $this->serviceCalculations->getTotalPrice($service);
            }
        }
        $defaultAmount = ($defaultAmount * $paymentPlan->getSmallestUnitMultiplier()) - $alreadySubscribedAmount;

        $paymentPlan->setPeriod($period ?? 1);
        $paymentPlan->setAmountInSmallestUnit((int) max(0, round($defaultAmount)));
    }

    public function handleCreateActive(PaymentPlan $paymentPlan): void
    {
        $paymentPlan->setActive(true);
        $paymentPlan->setStatus(PaymentPlan::STATUS_PENDING);

        if ($paymentPlan->getProvider() === PaymentPlan::PROVIDER_IPPAY) {
            $paymentPlan->setNextPaymentDate(clone $paymentPlan->getStartDate());
        }

        $this->handleCreate($paymentPlan);
    }

    public function handleCreate(PaymentPlan $paymentPlan): void
    {
        if ($service = $paymentPlan->getService()) {
            $paymentPlan->setAmountInSmallestUnit(
                (int) round(
                    $this->serviceCalculations->getTotalPrice($service) * $paymentPlan->getSmallestUnitMultiplier()
                )
            );
            $paymentPlan->setPeriod($service->getTariffPeriodMonths());
            $paymentPlan->setLinked(true);
        }

        $amount = $paymentPlan->getAmountInSmallestUnit() / $paymentPlan->getSmallestUnitMultiplier();
        $paymentPlan->setName(
            sprintf(
                '%s / %s - %s',
                $this->formatter->formatCurrency(
                    $amount,
                    $paymentPlan->getCurrency()->getCode(),
                    $paymentPlan->getClient()->getOrganization()->getLocale()
                ),
                $this->translator->trans(TariffPeriod::PERIOD_REPLACE_STRING[$paymentPlan->getPeriod()]),
                $paymentPlan->getClient()->getNameForView()
            )
        );

        $this->entityManager->persist($paymentPlan);
        $this->entityManager->flush();
        $this->cleanUp($paymentPlan->getClient());
    }

    public function handleUnlink(PaymentPlan $paymentPlan): void
    {
        $paymentPlan->setLinked(false);
        $this->entityManager->flush();
    }

    public function updateAmount(PaymentPlan $paymentPlan, int $newAmountInSmallestUnit): bool
    {
        if (! $paymentPlan->isActive()) {
            return true;
        }

        try {
            switch ($paymentPlan->getProvider()) {
                case PaymentPlan::PROVIDER_STRIPE:
                case PaymentPlan::PROVIDER_STRIPE_ACH:
                    $newPaymentPlan = $this->updateAmountStripe($paymentPlan, $newAmountInSmallestUnit);

                    break;
                default:
                    return false;
            }
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Amount cannot be changed. Reason: %s', $exception->getMessage()));

            return false;
        }

        if ($this->options->get(Option::NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED)) {
            $this->sendSubscriptionAmountChangedEmail($newPaymentPlan, $paymentPlan);
        }

        $this->transactionDispatcher->transactional(
            function () use ($newPaymentPlan, $paymentPlan) {
                yield new PaymentPlanEditEvent($newPaymentPlan, $paymentPlan);
            }
        );

        return true;
    }

    private function deleteAllCustomerDataWhenNotNeededAnymore(Client $client): void
    {
        if (! $client->getActivePaymentPlans()->isEmpty()) {
            return;
        }

        $organization = $client->getOrganization();
        $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);

        // Stripe
        if ($organization->hasStripe($isSandbox) && $client->getStripeCustomerId()) {
            try {
                $customer = $this->stripeCustomer->retrieve(
                    $organization->getStripeSecretKey($isSandbox),
                    $client->getStripeCustomerId()
                );
                $customer->delete();
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Cannot delete customer. Reason: %s', $exception->getMessage()));
            }
        }

        // Authorize.Net
        if ($organization->hasAuthorizeNet($isSandbox) && $client->getAnetCustomerProfileId()) {
            try {
                $this->anetFactory->create($organization, $isSandbox)->deleteCustomerProfile(
                    $client->getAnetCustomerProfileId()
                );
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Cannot delete customer. Reason: %s', $exception->getMessage()));
            }
        }
    }

    private function updateAmountStripe(PaymentPlan $paymentPlan, int $newAmountInSmallestUnit): PaymentPlan
    {
        $isSandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $client = $paymentPlan->getClient();
        $organization = $client->getOrganization();

        Stripe::setApiKey($organization->getStripeSecretKey($isSandbox));

        $newPaymentPlan = new PaymentPlan();
        $this->setDefaults($newPaymentPlan, $paymentPlan->getClient());
        $newPaymentPlan->setPeriod($paymentPlan->getPeriod());
        $newPaymentPlan->setAmountInSmallestUnit($newAmountInSmallestUnit);
        $newPaymentPlan->setProvider(PaymentPlan::PROVIDER_STRIPE);
        $newPaymentPlan->setStatus(PaymentPlan::STATUS_PENDING);
        $newPaymentPlan->generateProviderPlanId();
        $newPaymentPlan->setStartDate(new \DateTime('today midnight'));
        $newPaymentPlan->setLinked($paymentPlan->isLinked());
        $this->handleCreate($newPaymentPlan);

        $existingPlan = null;
        try {
            $existingPlan = Plan::retrieve($newPaymentPlan->getProviderPlanId());
            $existingPlan->delete();
        } catch (Error\InvalidRequest $e) {
            if ($e->getHttpStatus() !== 404) {
                throw $e;
            }
        }

        Plan::create(
            [
                'amount' => $newPaymentPlan->getAmountInSmallestUnit(),
                'interval' => 'month',
                'interval_count' => $newPaymentPlan->getPeriod(),
                'product' => [
                    'name' => $newPaymentPlan->getName(),
                ],
                'currency' => $newPaymentPlan->getCurrency()->getCode(),
                'id' => $newPaymentPlan->getProviderPlanId(),
                'metadata' => [
                    'paymentPlanId' => $newPaymentPlan->getId(),
                    'clientId' => $client->getId(),
                ],
            ]
        );

        Subscription::update(
            $paymentPlan->getProviderSubscriptionId(),
            [
                'plan' => $newPaymentPlan->getProviderPlanId(),
                'prorate' => false,
            ]
        );
        $newPaymentPlan->setActive(true);
        $newPaymentPlan->setStatus(PaymentPlan::STATUS_ACTIVE);
        $newPaymentPlan->setProviderSubscriptionId($paymentPlan->getProviderSubscriptionId());
        $newPaymentPlan->setService($paymentPlan->getService());
        $this->entityManager->flush($newPaymentPlan);

        $oldPlan = Plan::retrieve($paymentPlan->getProviderPlanId());
        $oldPlan->delete();
        $this->deleteSubscription($paymentPlan, false);

        return $newPaymentPlan;
    }

    /**
     * Cleans up old inactive payment plans with NULL canceled date.
     * These can be created if user exits the subscription screen and then tries to subscribe again.
     */
    private function cleanUp(Client $client): void
    {
        $this->entityManager->getRepository(PaymentPlan::class)
            ->createQueryBuilder('pp')
            ->delete(PaymentPlan::class, 'pp')
            ->where('pp.active = false')
            ->andWhere('pp.client = :client')
            ->andWhere('pp.canceledDate IS NULL')
            ->andWhere('pp.createdDate < :olderThan')
            ->setParameter('client', $client->getId())
            ->setParameter('olderThan', new \DateTime('30 days ago'), UtcDateTimeType::NAME)
            ->getQuery()->execute();
    }

    private function sendSubscriptionCancelledEmail(PaymentPlan $paymentPlan): void
    {
        $notificationTemplate = $this->entityManager
            ->getRepository(NotificationTemplate::class)
            ->find(NotificationTemplate::SUBSCRIPTION_CANCELLED);

        if (! $notificationTemplate) {
            throw new \RuntimeException(
                sprintf('Notification template %s not found', NotificationTemplate::SUBSCRIPTION_CANCELLED)
            );
        }

        $client = $paymentPlan->getClient();
        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setClient($client);
        $notification->setPaymentPlan($paymentPlan);

        $organization = $client->getOrganization();

        $billingEmails = $client->getBillingEmails();
        $message = new Message();
        $message->setClient($client);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setTo($billingEmails);
        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        if (! $billingEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );

            return;
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
    }

    private function sendSubscriptionAmountChangedEmail(PaymentPlan $newPaymentPlan, PaymentPlan $oldPaymentPlan): void
    {
        $notificationTemplate = $this->entityManager
            ->getRepository(NotificationTemplate::class)
            ->find(NotificationTemplate::SUBSCRIPTION_AMOUNT_CHANGED);

        if (! $notificationTemplate) {
            throw new \RuntimeException(
                sprintf('Notification template %s not found', NotificationTemplate::SUBSCRIPTION_AMOUNT_CHANGED)
            );
        }

        $client = $newPaymentPlan->getClient();
        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setClient($client);
        $notification->setPaymentPlan($newPaymentPlan);
        $notification->setPaymentPlanChange($newPaymentPlan, $oldPaymentPlan);

        $organization = $client->getOrganization();

        $billingEmails = $client->getBillingEmails();
        $message = new Message();
        $message->setClient($client);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setTo($billingEmails);
        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        if (! $billingEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );

            return;
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_MEDIUM);
    }
}
