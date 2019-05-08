<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class TaxMap extends AbstractMap
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
    public $agencyName;

    /**
     * @Type("double")
     */
    public $rate;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $selected;
}
