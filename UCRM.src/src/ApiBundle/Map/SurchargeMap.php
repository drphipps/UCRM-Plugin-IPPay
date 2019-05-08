<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class SurchargeMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     */
    public $name;

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

    /**
     * @Type("integer")
     */
    public $taxId;
}
