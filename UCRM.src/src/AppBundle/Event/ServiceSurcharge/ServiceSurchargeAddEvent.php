<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\ServiceSurcharge;

use AppBundle\Entity\ServiceSurcharge;
use Symfony\Component\EventDispatcher\Event;

class ServiceSurchargeAddEvent extends Event
{
    /**
     * @var ServiceSurcharge
     */
    private $serviceSurcharge;

    public function __construct(ServiceSurcharge $serviceSurcharge)
    {
        $this->serviceSurcharge = $serviceSurcharge;
    }

    public function getServiceSurcharge(): ServiceSurcharge
    {
        return $this->serviceSurcharge;
    }
}
