<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Stripe;

use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentProvider;
use AppBundle\Entity\PaymentStripe;
use AppBundle\Entity\PaymentToken;
use AppBundle\Facade\PaymentFacade;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;

class Charge
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
     * @var bool
     */
    private $sandbox;

    public function __construct(EntityManagerInterface $entityManager, PaymentFacade $paymentFacade, bool $sandbox)
    {
        $this->entityManager = $entityManager;
        $this->paymentFacade = $paymentFacade;
        $this->sandbox = $sandbox;
    }

    public function process(Request $request, PaymentToken $paymentToken, int $amountInSmallestUnit): Payment
    {
        $invoice = $paymentToken->getInvoice();
        $organization = $invoice->getOrganization();
        $currency = $invoice->getCurrency();
        $invoiceId = $invoice->getInvoiceNumber();
        $client = $invoice->getClient();
        $amount = $amountInSmallestUnit / (10 ** $currency->getFractionDigits());

        Stripe::setApiKey($organization->getStripeSecretKey($this->sandbox));

        $charge = \Stripe\Charge::create(
            [
                'source' => $request->request->get('stripeToken'),
                'amount' => $amountInSmallestUnit,
                'currency' => $currency->getCode(),
                'metadata' => [
                    'order_id' => $invoiceId,
                ],
            ]
        );

        $response = $charge->getLastResponse();

        $responseBody = Json::decode($response->body);

        $paymentStripe = new PaymentStripe();
        $paymentStripe->setOrganization($organization);
        $paymentStripe->setClient($client);
        if (is_array($response->headers)) {
            $headers = array_change_key_case($response->headers, CASE_LOWER);
            $paymentStripe->setRequestId($headers['request-id'] ?? null);
        }
        $paymentStripe->setStripeId($responseBody->id);
        $paymentStripe->setBalanceTransaction($responseBody->balance_transaction);
        $paymentStripe->setCustomer($responseBody->customer);
        $paymentStripe->setAmount($responseBody->amount);
        $paymentStripe->setSourceCardId($responseBody->source->id);
        $paymentStripe->setSourceFingerprint($responseBody->source->fingerprint ?? null);
        $paymentStripe->setSourceName($responseBody->source->name ?? null);
        $paymentStripe->setStatus($responseBody->status);

        $payment = new Payment();
        $payment->setMethod(Payment::METHOD_STRIPE);
        $payment->setCreatedDate(new \DateTime());
        $payment->setAmount($amount);
        $payment->setClient($client);
        $payment->setCurrency($invoice->getCurrency());
        $payment->setProvider($this->entityManager->find(PaymentProvider::class, PaymentProvider::ID_STRIPE));
        $payment->setPaymentDetailsId($paymentStripe->getId());

        $this->paymentFacade->handleCreateOnlinePayment($payment, $paymentStripe, $paymentToken);

        return $payment;
    }
}
