<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class TotalTaxMap extends AbstractMap
{
    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $name;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $totalValue;
}
