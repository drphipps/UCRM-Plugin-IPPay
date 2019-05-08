<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class FinancialItemMap extends AbstractMap
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
    public $type;

    /**
     * @Type("string")
     */
    public $label;

    /**
     * @Type("double")
     */
    public $price;

    /**
     * @Type("double")
     */
    public $quantity;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $total;

    /**
     * @Type("string")
     */
    public $unit;

    /**
     * @Type("integer")
     */
    public $tax1Id;

    /**
     * @Type("integer")
     */
    public $tax2Id;

    /**
     * @Type("integer")
     */
    public $tax3Id;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $serviceId;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $serviceSurchargeId;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $productId;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $discountPrice;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $discountQuantity;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $discountTotal;
}
