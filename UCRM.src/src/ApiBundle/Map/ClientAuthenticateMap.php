<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class ClientAuthenticateMap extends ClientMap
{
    /**
     * @Type("string")
     * @Assert\NotBlank()
     */
    public $password;

    /**
     * @Type("string")
     * @Assert\NotBlank()
     */
    public $username;
}
