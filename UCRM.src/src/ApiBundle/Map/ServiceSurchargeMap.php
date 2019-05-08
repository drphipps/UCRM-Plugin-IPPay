<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ServiceSurchargeMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $serviceId;

    /**
     * @Type("integer")
     */
    public $surchargeId;

    /**
     * @Type("string")
     */
    public $invoiceLabel;

    /**
     * @Type("double")
     */
    public $price;

    /**
     * @Type("boolean")
     */
    public $taxable;
}
