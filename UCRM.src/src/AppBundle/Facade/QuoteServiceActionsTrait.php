<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Service;

trait QuoteServiceActionsTrait
{
    private function activateQuotedService(Service $service): bool
    {
        if ($service->getStatus() !== Service::STATUS_QUOTED) {
            return false;
        }

        $service->setStatus(Service::STATUS_ACTIVE);
        $service->calculateStatus();

        return true;
    }
}
