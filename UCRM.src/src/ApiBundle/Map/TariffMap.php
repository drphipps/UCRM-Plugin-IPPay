<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class TariffMap extends AbstractMap
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
     * @Type("integer")
     */
    public $downloadBurst;

    /**
     * @Type("integer")
     */
    public $uploadBurst;

    /**
     * @Type("float")
     */
    public $downloadSpeed;

    /**
     * @Type("float")
     */
    public $uploadSpeed;

    /**
     * @Type("integer")
     */
    public $dataUsageLimit;

    /**
     * @Type("integer")
     */
    public $organizationId;

    /**
     * @Type("boolean")
     */
    public $taxable;

    /**
     * @Type("integer")
     */
    public $taxId;

    /**
     * @Type("array<ApiBundle\Map\TariffPeriodMap>")
     */
    public $periods = [];
}
