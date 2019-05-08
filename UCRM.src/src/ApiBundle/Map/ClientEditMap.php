<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\Type;

class ClientEditMap extends ClientMap
{
    /**
     * @Type("string")
     */
    public $password;
}
