<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\MercadoPago;

use AppBundle\Entity\Organization;

class NotificationsHandlerFactory
{
    /**
     * @var MercadoPagoFactory
     */
    private $mercadoPagoFactory;

    /**
     * @var OneTimePaymentHandler
     */
    private $oneTimePaymentHandler;

    /**
     * @var SubscriptionHandler
     */
    private $subscriptionHandler;

    public function __construct(
        MercadoPagoFactory $mercadoPagoFactory,
        OneTimePaymentHandler $oneTimePaymentHandler,
        SubscriptionHandler $subscriptionHandler
    ) {
        $this->mercadoPagoFactory = $mercadoPagoFactory;
        $this->oneTimePaymentHandler = $oneTimePaymentHandler;
        $this->subscriptionHandler = $subscriptionHandler;
    }

    public function create(Organization $organization): NotificationsHandler
    {
        $mercadoPago = $this->mercadoPagoFactory->create($organization);

        return new NotificationsHandler(
            $mercadoPago,
            $organization,
            $this->oneTimePaymentHandler,
            $this->subscriptionHandler
        );
    }
}
