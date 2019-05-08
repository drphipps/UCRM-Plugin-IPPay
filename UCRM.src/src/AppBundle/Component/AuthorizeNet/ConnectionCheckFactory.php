<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\AuthorizeNet;

use AppBundle\Entity\Organization;

class ConnectionCheckFactory
{
    public function create(Organization $organization, bool $sandbox): ConnectionCheck
    {
        $connectionCheck = new ConnectionCheck();
        $connectionCheck->setOrganization($organization);
        $connectionCheck->setSandbox($sandbox);

        return $connectionCheck;
    }
}
