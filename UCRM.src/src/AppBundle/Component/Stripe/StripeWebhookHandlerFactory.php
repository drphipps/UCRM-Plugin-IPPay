<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Component\Stripe\Webhook\ChargeSucceededEventHandler;
use AppBundle\Component\Stripe\Webhook\CustomerDeletedEventHandler;
use AppBundle\Component\Stripe\Webhook\CustomerSubscriptionDeletedEventHandler;
use AppBundle\Entity\Organization;
use AppBundle\Facade\ClientBankAccountFacade;

class StripeWebhookHandlerFactory
{
    /**
     * @var ClientBankAccountFacade
     */
    private $clientBankAccountFacade;

    /**
     * @var ChargeSucceededEventHandler
     */
    private $chargeSucceededEventHandler;

    /**
     * @var CustomerDeletedEventHandler
     */
    private $customerDeletedEventHandler;

    /**
     * @var CustomerSubscriptionDeletedEventHandler
     */
    private $customerSubscriptionDeletedEventHandler;

    public function __construct(
        ClientBankAccountFacade $clientBankAccountFacade,
        ChargeSucceededEventHandler $chargeSucceededEventHandler,
        CustomerDeletedEventHandler $customerDeletedEventHandler,
        CustomerSubscriptionDeletedEventHandler $customerSubscriptionDeletedEventHandler
    ) {
        $this->clientBankAccountFacade = $clientBankAccountFacade;
        $this->chargeSucceededEventHandler = $chargeSucceededEventHandler;
        $this->customerDeletedEventHandler = $customerDeletedEventHandler;
        $this->customerSubscriptionDeletedEventHandler = $customerSubscriptionDeletedEventHandler;
    }

    public function create(Organization $organization, bool $sandbox): StripeWebhookHandler
    {
        return new StripeWebhookHandler(
            $this->clientBankAccountFacade,
            $this->chargeSucceededEventHandler,
            $this->customerDeletedEventHandler,
            $this->customerSubscriptionDeletedEventHandler,
            $organization,
            $sandbox
        );
    }
}
