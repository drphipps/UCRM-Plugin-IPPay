<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentStripePending;
use AppBundle\Entity\PaymentToken;
use AppBundle\Facade\PaymentStripePendingFacade;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;

class ChargeAch
{
    public const METADATA_PAYMENT_SOURCE = 'UCRM';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentStripePendingFacade
     */
    private $paymentStripePendingFacade;

    /**
     * @var bool
     */
    private $sandbox;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentStripePendingFacade $paymentStripePendingFacade,
        bool $sandbox
    ) {
        $this->entityManager = $entityManager;
        $this->paymentStripePendingFacade = $paymentStripePendingFacade;
        $this->sandbox = $sandbox;
    }

    public function process(
        Request $request,
        PaymentToken $paymentToken,
        ClientBankAccount $bankAccount
    ): PaymentStripePending {
        $invoice = $paymentToken->getInvoice();
        $organization = $invoice->getOrganization();
        $currency = $invoice->getCurrency()->getCode();
        $invoiceId = $invoice->getInvoiceNumber();

        Stripe::setApiKey($organization->getStripeSecretKey($this->sandbox));

        $amount = (float) $request->request->get('amount');

        $charge = \Stripe\Charge::create(
            [
                'customer' => $bankAccount->getStripeCustomerId(),
                'amount' => (int) round($amount * (10 ** $invoice->getCurrency()->getFractionDigits())),
                'currency' => $currency,
                'metadata' => [
                    'order_id' => $invoiceId,
                    'createdBy' => self::METADATA_PAYMENT_SOURCE,
                ],
                'source' => $bankAccount->getStripeBankAccountId(),
            ]
        );

        $response = $charge->getLastResponse();

        $responseBody = Json::decode($response->body);

        $paymentPending = new PaymentStripePending();

        $this->entityManager->transactional(
            function () use ($paymentPending, $amount, $invoice, $responseBody, $paymentToken, $bankAccount) {
                $paymentPending->setMethod(Payment::METHOD_STRIPE_ACH);
                $paymentPending->setCreatedDate(new \DateTime());
                $paymentPending->setAmount($amount);
                $paymentPending->setCurrency($invoice->getCurrency());
                $paymentPending->setPaymentDetailsId($responseBody->id);
                $paymentPending->setPaymentToken($paymentToken);
                $paymentPending->setClientBankAccount($bankAccount);

                $this->paymentStripePendingFacade->handleNew($paymentPending);
            }
        );

        return $paymentPending;
    }
}
