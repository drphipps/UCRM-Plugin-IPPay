<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;

interface FinancialItemSurchargeInterface extends FinancialItemInterface
{
    public function setServiceSurcharge(?ServiceSurcharge $serviceSurcharge): void;

    public function getServiceSurcharge(): ?ServiceSurcharge;

    public function setService(?Service $service): void;

    public function getService(): ?Service;
}
