<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Entity\ReportDataUsage;
use AppBundle\Entity\Service;
use AppBundle\Util\DateTimeFactory;

class ReportDataUsageFactory
{
    /**
     * @var TableDataProvider
     */
    private $tableDataProvider;

    public function __construct(TableDataProvider $tableDataProvider)
    {
        $this->tableDataProvider = $tableDataProvider;
    }

    public function createByService(Service $service): ?ReportDataUsage
    {
        $periods = $this->tableDataProvider->getTableDataWithCorrectionByPeriod($service);
        $currentPeriod = current($periods);
        $lastPeriod = next($periods);

        if (
            $lastPeriod
            && in_array($service->getStatus(), [Service::STATUS_DEFERRED, Service::STATUS_ENDED], true)
            && $lastPeriod['invoicedTo'] < new \DateTimeImmutable(
                sprintf('-%s months', $service->getTariffPeriodMonths() * 2),
                new \DateTimeZone('UTC')
            )
        ) {
            return null;
        }

        if (! $currentPeriod || ! $currentPeriod['invoicedFrom'] instanceof \DateTimeInterface) {
            return null;
        }

        $reportDataUsage = new ReportDataUsage();
        $reportDataUsage->setReportCreated(new \DateTime());
        $reportDataUsage->setService($service);

        if ($currentPeriod['invoicedTo'] >= new \DateTimeImmutable('midnight', new \DateTimeZone('UTC'))) {
            $reportDataUsage->setCurrentPeriodStart(DateTimeFactory::createFromInterface($currentPeriod['invoicedFrom']));
            $reportDataUsage->setCurrentPeriodEnd(DateTimeFactory::createFromInterface($currentPeriod['invoicedTo']));
            $reportDataUsage->setCurrentPeriodDownload($currentPeriod['download']);
            $reportDataUsage->setCurrentPeriodUpload($currentPeriod['upload']);
        } else {
            $lastPeriod = $currentPeriod;
        }

        if ($lastPeriod) {
            $reportDataUsage->setLastPeriodStart(DateTimeFactory::createFromInterface($lastPeriod['invoicedFrom']));
            $reportDataUsage->setLastPeriodEnd(DateTimeFactory::createFromInterface($lastPeriod['invoicedTo']));
            $reportDataUsage->setLastPeriodDownload($lastPeriod['download']);
            $reportDataUsage->setLastPeriodUpload($lastPeriod['upload']);
        }

        return $reportDataUsage;
    }
}
