<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\ServiceIp;

use AppBundle\Entity\ServiceIp;
use Symfony\Component\EventDispatcher\Event;

final class ServiceIpDeleteEvent extends Event
{
    /**
     * @var ServiceIp
     */
    private $serviceIp;

    public function __construct(ServiceIp $serviceIp)
    {
        $this->serviceIp = $serviceIp;
    }

    public function getServiceIp(): ServiceIp
    {
        return $this->serviceIp;
    }
}
