<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentMercadoPago;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentFacade;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;

class SubscriptionHandler
{
    private const SUBSCRIPTION_STATUS_PENDING = 'pending';
    private const SUBSCRIPTION_STATUS_AUTHORIZED = 'authorized';
    private const SUBSCRIPTION_STATUS_PAUSED = 'paused';
    private const SUBSCRIPTION_STATUS_CANCELLED = 'cancelled';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var ObjectRepository
     */
    private $mercadoPagoRepository;

    /**
     * @var ObjectRepository
     */
    private $currencyRepository;

    public function __construct(EntityManagerInterface $entityManager, PaymentFacade $paymentFacade)
    {
        $this->entityManager = $entityManager;
        $this->paymentFacade = $paymentFacade;
        $this->mercadoPagoRepository = $this->entityManager->getRepository(PaymentMercadoPago::class);
        $this->currencyRepository = $this->entityManager->getRepository(Currency::class);
    }

    public function handlePayment(array $paymentInfo, Organization $organization): void
    {
        $paymentData = $paymentInfo['response']['collection'] ?? false;
        if (! $paymentData || $paymentData['status'] !== NotificationsHandler::PAYMENT_STATUS_APPROVED) {
            return;
        }

        $paymentPlan = $this->getPaymentPlan($paymentData['external_reference']);
        if (! $paymentPlan || $this->mercadoPagoRepository->findOneBy(['mercadoPagoId' => $paymentData['id']])) {
            // Does not belong to UCRM payment plan, or is already processed.

            return;
        }

        $client = $paymentPlan->getClient();

        $paymentMercadoPago = new PaymentMercadoPago();
        $paymentMercadoPago->setOrganization($organization);
        $paymentMercadoPago->setClient($client);
        $paymentMercadoPago->setMercadoPagoId((string) $paymentData['id']);
        $paymentMercadoPago->setAmount($paymentData['transaction_amount']);
        $paymentMercadoPago->setCurrency($paymentData['currency_id']);

        /** @var Currency|null $currency */
        $currency = $this->currencyRepository->findOneBy(
            [
                'code' => $paymentMercadoPago->getCurrency(),
            ]
        );

        if ($client && $client->getOrganization()->getCurrency() !== $currency) {
            $client = null;
        }

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_MERCADO_PAGO_SUBSCRIPTION);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($paymentMercadoPago->getAmount());
        $payment->setClient($client);
        $payment->setCurrency($currency);

        $this->paymentFacade->handleCreateOnlinePaymentUsingSubscription($payment, $paymentMercadoPago);
    }

    public function handlePreapproval(array $preapproval): void
    {
        if (! ($preapproval['response']['external_reference'] ?? false)) {
            return;
        }

        $paymentPlan = $this->getPaymentPlan($preapproval['response']['external_reference']);
        if (! $paymentPlan) {
            return;
        }

        $this->entityManager->transactional(
            function () use ($preapproval, $paymentPlan) {
                $paymentPlan->setProviderPlanId($preapproval['response']['id']);
                switch ($preapproval['response']['status']) {
                    case self::SUBSCRIPTION_STATUS_AUTHORIZED:
                        $paymentPlan->setActive(true);
                        $paymentPlan->setStatus(PaymentPlan::STATUS_ACTIVE);
                        $paymentPlan->setCanceledDate(null);

                        break;
                    case self::SUBSCRIPTION_STATUS_PAUSED:
                        $paymentPlan->setActive(true);
                        $paymentPlan->setStatus(PaymentPlan::STATUS_PAUSED);
                        $paymentPlan->setCanceledDate(new \DateTime());

                        break;
                    case self::SUBSCRIPTION_STATUS_CANCELLED:
                        $paymentPlan->setActive(false);
                        $paymentPlan->setStatus(PaymentPlan::STATUS_CANCELLED);
                        $paymentPlan->setCanceledDate(new \DateTime());

                        break;
                    case self::SUBSCRIPTION_STATUS_PENDING:
                        // Ignore, user started subscription process, but there is nothing to do in UCRM with that.

                        break;
                }
            }
        );
    }

    private function getPaymentPlan(?string $externalReference): ?PaymentPlan
    {
        if (
            ! $externalReference
            || ! Strings::startsWith($externalReference, NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_PLAN)
        ) {
            return null;
        }

        return $this->entityManager->find(
            PaymentPlan::class,
            (int) Strings::after($externalReference, NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_PLAN)
        );
    }
}
