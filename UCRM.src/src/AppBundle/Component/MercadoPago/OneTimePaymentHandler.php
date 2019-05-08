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
use AppBundle\Entity\PaymentToken;
use AppBundle\Facade\PaymentFacade;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;

class OneTimePaymentHandler
{
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

    public function handle(array $merchantOrderInfo, Organization $organization): void
    {
        $paymentToken = $this->getPaymentToken($merchantOrderInfo);
        if (! $paymentToken) {
            // Does not belong to UCRM.

            return;
        }

        foreach ($merchantOrderInfo['response']['payments'] as $paymentInfo) {
            if ($paymentInfo['status'] === NotificationsHandler::PAYMENT_STATUS_APPROVED) {
                $this->processPaymentWithToken($paymentInfo, $paymentToken, $organization);
            }
        }
    }

    private function getPaymentToken(array $merchantOrderInfo): ?PaymentToken
    {
        $externalReference = $merchantOrderInfo['response']['external_reference'];
        if (
            ! $externalReference
            || ! Strings::startsWith($externalReference, NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_TOKEN)
        ) {
            return null;
        }

        return $this->entityManager->find(
            PaymentToken::class,
            (int) Strings::after($externalReference, NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_TOKEN)
        );
    }

    private function processPaymentWithToken(
        array $paymentInfo,
        PaymentToken $paymentToken,
        Organization $organization
    ): void {
        if ($this->mercadoPagoRepository->findOneBy(['mercadoPagoId' => $paymentInfo['id']])) {
            // Payment is already processed.

            return;
        }

        $invoice = $paymentToken->getInvoice();
        $client = $invoice->getClient();

        $paymentMercadoPago = new PaymentMercadoPago();
        $paymentMercadoPago->setOrganization($organization);
        $paymentMercadoPago->setClient($client);
        $paymentMercadoPago->setMercadoPagoId((string) $paymentInfo['id']);
        $paymentMercadoPago->setAmount($paymentInfo['transaction_amount']);
        $paymentMercadoPago->setCurrency($paymentInfo['currency_id']);

        /** @var Currency|null $currency */
        $currency = $this->currencyRepository->findOneBy(
            [
                'code' => $paymentMercadoPago->getCurrency(),
            ]
        );

        if ($client && $client->getOrganization()->getCurrency() !== $currency) {
            $client = null;
            $invoice = null;
        }

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_MERCADO_PAGO);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($paymentMercadoPago->getAmount());
        $payment->setClient($client);
        $payment->setCurrency($currency);

        $this->paymentFacade->handleCreateOnlinePayment($payment, $paymentMercadoPago, $paymentToken);
    }
}
