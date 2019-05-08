<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Component\Service\PeriodDataUsageData;
use AppBundle\Entity\Service;
use AppBundle\Util\UnitConverter\BinaryConverter;

class ServiceDataUsageProvider
{
    private const ROUNDING_DECIMAL_PRECISION = 4;

    /**
     * @var ServiceAccountingCorrectionDataProvider
     */
    private $serviceAccountingCorrectionDataProvider;

    /**
     * @var TableDataProvider
     */
    private $tableDataProvider;

    public function __construct(
        ServiceAccountingCorrectionDataProvider $serviceAccountingCorrectionDataProvider,
        TableDataProvider $tableDataProvider
    ) {
        $this->serviceAccountingCorrectionDataProvider = $serviceAccountingCorrectionDataProvider;
        $this->tableDataProvider = $tableDataProvider;
    }

    private function getDataUsageCount(
        Service $service,
        float $netflowDownload,
        float $correctionDownload,
        float $netflowUpload,
        float $correctionUpload,
        string $unit
    ): PeriodDataUsageData {
        $usageData = new PeriodDataUsageData();
        $usageData->unit = $unit;
        $usageData->download = $this->convert($netflowDownload + $correctionDownload, BinaryConverter::UNIT_BYTE, $unit);
        $usageData->upload = $this->convert($netflowUpload + $correctionUpload, BinaryConverter::UNIT_BYTE, $unit);
        $usageData->downloadLimit = $service->getTariff() && $service->getTariff()->getDataUsageLimit() !== null
                ? $this->convert($service->getTariff()->getDataUsageLimit(), BinaryConverter::UNIT_GIBI, $unit)
                : null;

        return $usageData;
    }

    public function getDataInDay(Service $service, \DateTimeImmutable $dateTime, string $unit): PeriodDataUsageData
    {
        $netflowDataUsage = $this->tableDataProvider->getDataInDay($service, $dateTime);
        $netflowDownload = $netflowDataUsage ? $netflowDataUsage->getDownload() : 0;
        $netflowUpload = $netflowDataUsage ? $netflowDataUsage->getUpload() : 0;

        $correctionDataUsage = $this->serviceAccountingCorrectionDataProvider->getCorrectionDataInDay(
            $service,
            $dateTime
        );
        $correctionDownload = $correctionDataUsage ? $correctionDataUsage->getDownload() : 0;
        $correctionUpload = $correctionDataUsage ? $correctionDataUsage->getUpload() : 0;

        $usageData = $this->getDataUsageCount(
            $service,
            $netflowDownload,
            $correctionDownload,
            $netflowUpload,
            $correctionUpload,
            $unit
        );
        $usageData->startDate = $dateTime;
        $usageData->endDate = $dateTime;

        return $usageData;
    }

    /**
     * @param \DateTimeInterface[] $period
     */
    public function getDataInPeriod(Service $service, array $period, string $unit): PeriodDataUsageData
    {
        if (empty($period['invoicedFrom'])) {
            throw new \InvalidArgumentException('Invalid period specified.');
        }
        $netflowDataUsage = $this->tableDataProvider->findDataByPeriodStart($service, $period['invoicedFrom']);
        $netflowDownload = $netflowDataUsage ? $netflowDataUsage->getDownload() : 0;
        $netflowUpload = $netflowDataUsage ? $netflowDataUsage->getUpload() : 0;

        $correctionDataUsage = $this->serviceAccountingCorrectionDataProvider->getCorrectionDataByPeriod(
            $service,
            $period
        );
        $correctionDownload = $correctionDataUsage ? $correctionDataUsage->getDownload() : 0;
        $correctionUpload = $correctionDataUsage ? $correctionDataUsage->getUpload() : 0;

        $usageData = $this->getDataUsageCount(
            $service,
            $netflowDownload,
            $correctionDownload,
            $netflowUpload,
            $correctionUpload,
            $unit
        );
        $usageData->startDate = $netflowDataUsage->getInvoicedFrom() ?: null;
        $usageData->endDate = $netflowDataUsage->getInvoicedTo() ?: null;

        return $usageData;
    }

    private function convert(float $param, string $unitFrom, string $unitTo): float
    {
        if ($unitFrom === $unitTo) {
            return $param;
        }

        $binaryConverter = new BinaryConverter($param, $unitFrom);

        return round(
            $binaryConverter->to($unitTo),
            self::ROUNDING_DECIMAL_PRECISION
        );
    }
}
