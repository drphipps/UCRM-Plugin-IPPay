<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class ClientZoneData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::CLIENT_ZONE_REACTIVATION)
     */
    public $clientZoneReactivation;

    /**
     * @var bool
     *
     * @Identifier(Option::SUSPENSION_ENABLE_POSTPONE)
     */
    public $suspensionEnablePostpone;

    /**
     * @var bool
     *
     * @Identifier(Option::TICKETING_ENABLED)
     */
    public $ticketingEnabled;

    /**
     * @var bool
     *
     * @Identifier(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
     */
    public $subscriptionsEnabledCustom;

    /**
     * @var bool
     *
     * @Identifier(Option::SUBSCRIPTIONS_ENABLED_LINKED)
     */
    public $subscriptionsEnabledLinked;

    /**
     * @var bool
     *
     * @Identifier(Option::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE)
     */
    public $paymentAmountChange;

    /**
     * @var bool
     *
     * @Identifier(Option::CLIENT_ZONE_PAYMENT_DETAILS)
     */
    public $paymentDetails;

    /**
     * @var bool
     *
     * @Identifier(Option::CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION)
     */
    public $servicePlanShapingInformation;
}
