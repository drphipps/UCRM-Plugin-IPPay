<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\ServiceDevice;

use AppBundle\Entity\ServiceDevice;
use Symfony\Component\EventDispatcher\Event;

final class ServiceDeviceEditEvent extends Event
{
    /**
     * @var ServiceDevice
     */
    private $serviceDevice;

    /**
     * @var ServiceDevice
     */
    private $serviceDeviceBeforeUpdate;

    public function __construct(ServiceDevice $service, ServiceDevice $serviceDeviceBeforeUpdate)
    {
        $this->serviceDevice = $service;
        $this->serviceDeviceBeforeUpdate = $serviceDeviceBeforeUpdate;
    }

    public function getServiceDevice(): ServiceDevice
    {
        return $this->serviceDevice;
    }

    public function getServiceDeviceBeforeUpdate(): ServiceDevice
    {
        return $this->serviceDeviceBeforeUpdate;
    }
}
