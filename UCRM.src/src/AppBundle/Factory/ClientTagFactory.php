<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Entity\ClientTag;

class ClientTagFactory
{
    public function create(): ClientTag
    {
        return new ClientTag();
    }
}
