<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\ServiceSurcharge;

use AppBundle\Entity\ServiceSurcharge;
use Symfony\Component\EventDispatcher\Event;

class ServiceSurchargeEditEvent extends Event
{
    /**
     * @var ServiceSurcharge
     */
    private $serviceSurcharge;

    /**
     * @var ServiceSurcharge
     */
    private $serviceSurchargeBeforeUpdate;

    public function __construct(ServiceSurcharge $serviceSurcharge, ServiceSurcharge $serviceSurchargeBeforeUpdate)
    {
        $this->serviceSurcharge = $serviceSurcharge;
        $this->serviceSurchargeBeforeUpdate = $serviceSurchargeBeforeUpdate;
    }

    public function getServiceSurcharge(): ServiceSurcharge
    {
        return $this->serviceSurcharge;
    }

    public function getServiceSurchargeBeforeUpdate(): ServiceSurcharge
    {
        return $this->serviceSurchargeBeforeUpdate;
    }
}
