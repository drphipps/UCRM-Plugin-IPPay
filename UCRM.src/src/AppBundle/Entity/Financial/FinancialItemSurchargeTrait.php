<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use Doctrine\ORM\Mapping as ORM;

trait FinancialItemSurchargeTrait
{
    /**
     * @var ServiceSurcharge|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ServiceSurcharge")
     * @ORM\JoinColumn(name="service_surcharge_id", referencedColumnName="service_surcharge_id", onDelete="SET NULL")
     */
    protected $serviceSurcharge;

    /**
     * @var Service|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id")
     */
    protected $service;

    public function setServiceSurcharge(?ServiceSurcharge $serviceSurcharge): void
    {
        $this->serviceSurcharge = $serviceSurcharge;
    }

    public function getServiceSurcharge(): ?ServiceSurcharge
    {
        return $this->serviceSurcharge;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }
}
