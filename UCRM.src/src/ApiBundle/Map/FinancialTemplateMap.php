<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class FinancialTemplateMap extends AbstractMap
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
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $createdDate;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $isOfficial;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $isValid;
}
