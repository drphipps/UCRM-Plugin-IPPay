<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map\Request;

use AppBundle\Entity\Client;
use AppBundle\Entity\Site;

class NetworkMapRequest
{
    /**
     * @var bool
     */
    public $excludeLeads = false;

    /**
     * @var Client|null
     */
    public $clientLead;

    /**
     * @var Site[]
     */
    public $sites = [];

    public function isClear(): bool
    {
        return ! $this->excludeLeads
            && ! $this->clientLead
            && ! $this->sites;
    }
}
