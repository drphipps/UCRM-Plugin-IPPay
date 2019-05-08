<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ClientAttributeMap extends AbstractMap
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
    public $clientId;

    /**
     * @Type("integer")
     */
    public $customAttributeId;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $name;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $key;

    /**
     * @Type("string")
     */
    public $value;
}
