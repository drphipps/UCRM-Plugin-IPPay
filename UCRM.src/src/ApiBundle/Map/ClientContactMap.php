<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ClientContactMap extends AbstractMap
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
     * @Type("string")
     */
    public $email;

    /**
     * @Type("string")
     */
    public $phone;

    /**
     * @Type("string")
     */
    public $name;

    /**
     * @Type("boolean")
     */
    public $isBilling;

    /**
     * @Type("boolean")
     */
    public $isContact;

    /**
     * @Type("array<ApiBundle\Map\ContactTypeMap>")
     */
    public $types;
}
