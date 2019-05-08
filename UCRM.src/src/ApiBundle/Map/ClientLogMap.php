<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class ClientLogMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     */
    public $userId;

    /**
     * @Type("integer")
     */
    public $clientId;

    /**
     * @Type("DateTime")
     */
    public $createdDate;

    /**
     * @Type("string")
     */
    public $message;
}
