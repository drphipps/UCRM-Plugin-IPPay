<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\NetFlow;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccounting;
use AppBundle\Entity\ServiceAccountingCorrection;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Invoicing;
use Doctrine\ORM\EntityManagerInterface;

class TableDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array|NetFlowInPeriodData[]
     */
    public function getTableDataByPeriodWithoutCorrection(Service $service): array
    {
        $invoicedPeriods = $this->getInvoicedPeriods($service);
        $firstPeriod = reset($invoicedPeriods);

        $invoicingStart = $firstPeriod['invoicedFrom']->format(\DateTime::ISO8601);
        // UTC is required, because whole Invoicing class works with UTC.
        $timezone = new \DateTimeZone('UTC');
        $since = new \DateTimeImmutable($invoicingStart, $timezone);

        $serviceDataUsageByDay = $this->entityManager->getRepository(ServiceAccounting::class)->getChartData(
            $since,
            $service
        );
        $return = [];
        foreach ($invoicedPeriods as $key => $invoicedPeriod) {
            if (
                null === $invoicedPeriod['invoicedTo']
                || $invoicedPeriod['invoicedFrom'] > new \DateTimeImmutable('midnight', $timezone)
            ) {
                continue;
            }

            $netflowInPeriodData = new NetFlowInPeriodData();
            $netflowInPeriodData->setInvoicedTo(
                \DateTimeImmutable::createFromFormat(
                    'Y-m-d|',
                    $invoicedPeriod['invoicedTo']->format('Y-m-d'),
                    $timezone
                )
            );
            if ($invoicedPeriod['invoicedFrom']) {
                $netflowInPeriodData->setInvoicedFrom(
                    new \DateTimeImmutable($invoicedPeriod['invoicedFrom']->format('Y-m-d'), $timezone)
                );
            }

            $return[$key] = $netflowInPeriodData;

            foreach ($serviceDataUsageByDay as $dataUsageInDay) {
                // There is just date in ServiceAccounting, no time, create new DateTime object with UTC timezone and 0 time.
                $date = DateTimeFactory::createFromFormat(
                    'Y-m-d|',
                    $dataUsageInDay['date']->format('Y-m-d'),
                    $timezone
                );
                if ($date >= $return[$key]->getInvoicedFrom()
                    && $date <= $return[$key]->getInvoicedTo()
                ) {
                    $return[$key]->addUpload((int) $dataUsageInDay['upload']);
                    $return[$key]->addDownload((int) $dataUsageInDay['download']);
                }
            }
        }

        return array_values(array_reverse($return));
    }

    public function getTableDataWithCorrectionByPeriod(Service $service): array
    {
        $serviceCorrections = $this->entityManager->getRepository(ServiceAccountingCorrection::class)->findBy(
            [
                'service' => $service,
            ]
        );

        $returnPeriods = [];
        foreach ($this->getTableDataByPeriodWithoutCorrection($service) as $netflowData) {
            $isCorrected = false;
            $return = [
                'invoicedFrom' => $netflowData->getInvoicedFrom(),
                'invoicedTo' => $netflowData->getInvoicedTo(),
                'download' => $netflowData->getDownload(),
                'upload' => $netflowData->getUpload(),
            ];

            foreach ($serviceCorrections as $serviceCorrection) {
                $date = DateTimeFactory::createFromFormat(
                    'Y-m-d|',
                    $serviceCorrection->getDate()->format('Y-m-d'),
                    new \DateTimeZone('UTC')
                );

                if ($date >= $netflowData->getInvoicedFrom()
                    && $date <= $netflowData->getInvoicedTo()
                ) {
                    $return['upload'] += $serviceCorrection->getUpload();
                    $return['download'] += $serviceCorrection->getDownload();
                    $isCorrected = true;
                }
            }

            if ($isCorrected) {
                $return['downloadNetflow'] = $netflowData->getDownload();
                $return['uploadNetflow'] = $netflowData->getUpload();
                $return['totalNetflow'] = $return['downloadNetflow'] + $return['uploadNetflow'];
            }

            $returnPeriods[] = $return;
        }

        return $returnPeriods;
    }

    public function getDataInDay(Service $service, \DateTimeInterface $date): ?ServiceAccounting
    {
        return $this->entityManager->getRepository(ServiceAccounting::class)
            ->findOneBy(
                [
                    'service' => $service,
                    'date' => $date,
                ]
            );
    }

    public function findDataByPeriodStart(Service $service, \DateTimeInterface $periodStart): ?NetFlowInPeriodData
    {
        foreach ($this->getTableDataByPeriodWithoutCorrection($service) as $tableDataPeriod) {
            if (
                $tableDataPeriod->getInvoicedFrom()
                && $tableDataPeriod->getInvoicedFrom()->format('Y-m-d') === $periodStart->format('Y-m-d')
            ) {
                return $tableDataPeriod;
            }
        }

        return null;
    }

    public function findPeriod(Service $service, \DateTimeImmutable $firstDayOfPeriod): ?array
    {
        foreach ($this->getInvoicedPeriods($service) as $invoicedPeriod) {
            if (
                null === $invoicedPeriod['invoicedFrom']
                || $firstDayOfPeriod->format('Y-m-d') !== $invoicedPeriod['invoicedFrom']->format('Y-m-d')
            ) {
                continue;
            }

            return $invoicedPeriod;
        }

        return null;
    }

    private function getInvoicedPeriods(Service $service): array
    {
        $serviceRepository = $this->entityManager->getRepository(Service::class);
        $periods = [];
        do {
            $periods = array_merge(
                Invoicing::getPeriodsForDataUsage($service, $service->getInvoicingStart()),
                $periods
            );
        } while ($service = $serviceRepository->findOneBy(['supersededByService' => $service]));

        return $periods;
    }
}
