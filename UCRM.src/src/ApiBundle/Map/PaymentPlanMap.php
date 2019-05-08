<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class PaymentPlanMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $name;

    /**
     * @Type("string")
     */
    public $provider;

    /**
     * @Type("string")
     */
    public $providerPlanId;

    /**
     * @Type("string")
     */
    public $providerSubscriptionId;

    /**
     * @Type("integer")
     */
    public $clientId;

    /**
     * @Type("integer")
     */
    public $currencyId;

    /**
     * @Type("double")
     */
    public $amount;

    /**
     * @Type("integer")
     */
    public $period;

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $createdDate;

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $canceledDate;

    /**
     * @Type("DateTime")
     */
    public $startDate;

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $nextPaymentDate;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $status;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $active;
}
