<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\Type;

class UserEditMap extends UserMap
{
    /**
     * @Type("string")
     */
    public $password;
}
