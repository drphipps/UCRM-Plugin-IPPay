<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\ServiceDevice;

use AppBundle\Entity\ServiceDevice;
use Symfony\Component\EventDispatcher\Event;

final class ServiceDeviceDeleteEvent extends Event
{
    /**
     * @var ServiceDevice
     */
    private $serviceDevice;

    public function __construct(ServiceDevice $serviceDevice)
    {
        $this->serviceDevice = $serviceDevice;
    }

    public function getServiceDevice(): ServiceDevice
    {
        return $this->serviceDevice;
    }
}
