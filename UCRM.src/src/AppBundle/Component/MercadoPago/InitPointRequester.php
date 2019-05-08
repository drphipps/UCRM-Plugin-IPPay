<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\PaymentToken;
use AppBundle\Service\PublicUrlGenerator;

/**
 * Handles requesting an init_point URL for both one-time payments and subscriptions.
 * All needed data are constructed and user then should be redirected to init_point URL to begin the payment process.
 *
 * @see https://www.mercadopago.com.mx/developers/en/solutions/payments/basic-checkout/receive-payments/
 * @see https://www.mercadopago.com.mx/developers/en/solutions/payments/basic-checkout/subscriptions/
 */
class InitPointRequester
{
    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var MercadoPagoFactory
     */
    private $mercadoPagoFactory;

    public function __construct(
        PublicUrlGenerator $publicUrlGenerator,
        MercadoPagoFactory $mercadoPagoFactory
    ) {
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->mercadoPagoFactory = $mercadoPagoFactory;
    }

    public function requestOneTimePaymentInitPoint(PaymentToken $paymentToken): string
    {
        $invoice = $paymentToken->getInvoice();
        $organization = $invoice->getOrganization();
        $mp = $this->mercadoPagoFactory->create($organization);

        $preferenceData = [
            'items' => [
                [
                    'title' => $invoice->getInvoiceNumber(),
                    'quantity' => 1,
                    'currency_id' => $invoice->getCurrency()->getCode(),
                    'unit_price' => $paymentToken->getAmount(),
                ],
            ],
            'payer' => [
                'name' => $invoice->getClientNameForView(),
                'email' => $invoice->getClient()->getFirstBillingEmail(),
            ],
            'back_urls' => [
                'success' => $this->publicUrlGenerator->generate('online_payment_success'),
                'pending' => $this->publicUrlGenerator->generate('online_payment_pending'),
                'failure' => $this->publicUrlGenerator->generate(
                    'online_payment_cancelled',
                    [
                        'token' => $paymentToken->getToken(),
                    ]
                ),
            ],
            'external_reference' => sprintf(
                '%s%d',
                NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_TOKEN,
                $paymentToken->getId()
            ),
        ];

        $preference = $mp->create_preference($preferenceData);

        return $mp->sandbox_mode(null)
            ? $preference['response']['sandbox_init_point']
            : $preference['response']['init_point'];
    }

    public function requestSubscriptionInitPoint(PaymentPlan $paymentPlan): string
    {
        $client = $paymentPlan->getClient();
        $organization = $client->getOrganization();
        $mp = $this->mercadoPagoFactory->create($organization);

        $preapprovalData = [
            'reason' => $paymentPlan->getName(),
            'payer_email' => $client->getFirstBillingEmail(),
            'auto_recurring' => [
                'frequency' => $paymentPlan->getPeriod(),
                'frequency_type' => 'months',
                'transaction_amount' => round(
                    $paymentPlan->getAmountInSmallestUnit() / $paymentPlan->getSmallestUnitMultiplier(),
                    (int) log10($paymentPlan->getSmallestUnitMultiplier())
                ),
                'currency_id' => $paymentPlan->getCurrency()->getCode(),
            ],
            'external_reference' => sprintf(
                '%s%d',
                NotificationsHandler::EXTERNAL_REFERENCE_PREFIX_PAYMENT_PLAN,
                $paymentPlan->getId()
            ),
            'back_url' => $this->publicUrlGenerator->generate(
                'mercado_pago_subscription_return',
                [
                    'paymentPlanId' => $paymentPlan->getId(),
                ],
                true
            ),
        ];

        // If the date is today, don't include in request, "now" is used automatically.
        // If it was included, it would have to be moved for example 5 minutes in the future
        // to prevent "date is in the past" fail.
        $startDate = $paymentPlan->getStartDate();
        if ($startDate && $startDate->format('Y-m-d') !== (new \DateTime())->format('Y-m-d')) {
            $preapprovalData['auto_recurring']['start_date'] = $startDate->format(\DateTime::RFC3339_EXTENDED);
        }

        $preapproval = $mp->create_preapproval_payment($preapprovalData);

        return $mp->sandbox_mode(null)
            ? $preapproval['response']['sandbox_init_point']
            : $preapproval['response']['init_point'];
    }
}
