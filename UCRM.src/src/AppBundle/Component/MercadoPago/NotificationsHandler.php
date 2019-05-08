<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\Organization;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles receiving notification requests from MercadoPago IPN service.
 * Both one-time payments and subscriptions are handled, verified and sent for further processing
 * to corresponding handler.
 *
 * @see https://www.mercadopago.com.mx/developers/en/solutions/payments/basic-checkout/receive-notifications/
 * @see https://www.mercadopago.com.mx/developers/en/api-docs/basic-checkout/ipn/
 * @see https://www.mercadopago.com.mx/developers/en/api-docs/basic-checkout/subscription-ipn/
 */
class NotificationsHandler
{
    public const EXTERNAL_REFERENCE_PREFIX_PAYMENT_TOKEN = 'UCRM_PAYMENT_TOKEN_';
    public const EXTERNAL_REFERENCE_PREFIX_PAYMENT_PLAN = 'UCRM_PAYMENT_PLAN_';

    private const QUERY_KEY_ID = 'id';
    private const QUERY_KEY_TOPIC = 'topic';

    private const TOPIC_AUTHORIZED_PAYMENT = 'authorized_payment';
    private const TOPIC_MERCHANT_ORDER = 'merchant_order';
    private const TOPIC_PAYMENT = 'payment';
    private const TOPIC_PREAPPROVAL = 'preapproval';

    public const PAYMENT_STATUS_APPROVED = 'approved';

    private const RESPONSE_STATUS_OK = 200;

    private const TEST_NOTIFICATION_ID = '12345';
    private const TEST_NOTIFICATION_TOPIC = 'payment';

    /**
     * @var \MP
     */
    private $mercadoPago;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var OneTimePaymentHandler
     */
    private $oneTimePaymentHandler;

    /**
     * @var SubscriptionHandler
     */
    private $subscriptionHandler;

    public function __construct(
        \MP $mercadoPago,
        Organization $organization,
        OneTimePaymentHandler $oneTimePaymentHandler,
        SubscriptionHandler $subscriptionHandler
    ) {
        $this->mercadoPago = $mercadoPago;
        $this->organization = $organization;
        $this->oneTimePaymentHandler = $oneTimePaymentHandler;
        $this->subscriptionHandler = $subscriptionHandler;
    }

    /**
     * @throws \MercadoPagoException
     */
    public function handleRequest(Request $request): void
    {
        // Verify request has required fields.
        if (
            ! $request->query->has(self::QUERY_KEY_ID)
            || ! $request->query->has(self::QUERY_KEY_TOPIC)
        ) {
            throw new \MercadoPagoException('Invalid request.', 400);
        }

        $id = (string) $request->query->get(self::QUERY_KEY_ID);
        $topic = (string) $request->query->get(self::QUERY_KEY_TOPIC);

        switch ($topic) {
            case self::TOPIC_PAYMENT:
                $paymentInfo = $this->getPaymentInfo((int) $id);
                if ($paymentInfo['status'] !== self::RESPONSE_STATUS_OK) {
                    return;
                }

                if ($merchantOrderId = $paymentInfo['response']['collection']['merchant_order_id']) {
                    $merchantOrderInfo = $this->getMerchantOrder((int) $merchantOrderId);
                    if ($merchantOrderInfo['status'] !== self::RESPONSE_STATUS_OK) {
                        return;
                    }

                    $this->oneTimePaymentHandler->handle(
                        $merchantOrderInfo,
                        $this->organization
                    );
                } else {
                    $this->subscriptionHandler->handlePayment($paymentInfo, $this->organization);
                }

                break;
            case self::TOPIC_MERCHANT_ORDER:
                $merchantOrderInfo = $this->getMerchantOrder((int) $id);
                if ($merchantOrderInfo['status'] !== self::RESPONSE_STATUS_OK) {
                    return;
                }

                $this->oneTimePaymentHandler->handle(
                    $merchantOrderInfo,
                    $this->organization
                );

                break;
            case self::TOPIC_AUTHORIZED_PAYMENT:
                $authorizedPaymentInfo = $this->mercadoPago->get(
                    sprintf(
                        '/authorized_payments/%s',
                        $id
                    )
                );
                if ($authorizedPaymentInfo['status'] !== self::RESPONSE_STATUS_OK) {
                    return;
                }

                $paymentId = $authorizedPaymentInfo['response']['payment']['id'] ?? false;
                if ($paymentId === false) {
                    return;
                }

                $paymentInfo = $this->getPaymentInfo((int) $paymentId);
                if ($paymentInfo['status'] !== self::RESPONSE_STATUS_OK) {
                    return;
                }

                $this->subscriptionHandler->handlePayment($paymentInfo, $this->organization);

                break;
            case self::TOPIC_PREAPPROVAL:
                $preapprovalInfo = $this->mercadoPago->get(
                    sprintf(
                        '/preapproval/%s',
                        $id
                    )
                );
                if ($preapprovalInfo['status'] !== self::RESPONSE_STATUS_OK) {
                    return;
                }

                $this->subscriptionHandler->handlePreapproval($preapprovalInfo);

                break;
        }
    }

    private function getPaymentInfo(int $id): array
    {
        return $this->mercadoPago->get(
            sprintf(
                '/collections/notifications/%d',
                $id
            )
        );
    }

    private function getMerchantOrder(int $id): array
    {
        return $this->mercadoPago->get(
            sprintf(
                '/merchant_orders/%d',
                $id
            )
        );
    }
}
