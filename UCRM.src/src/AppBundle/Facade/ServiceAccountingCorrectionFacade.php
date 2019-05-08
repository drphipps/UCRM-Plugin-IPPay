<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\DataProvider\ServiceAccountingCorrectionDataProvider;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccountingCorrection;
use AppBundle\Exception\ServicePeriodNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class ServiceAccountingCorrectionFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TableDataProvider
     */
    private $tableDataProvider;

    /**
     * @var ServiceAccountingCorrectionDataProvider
     */
    private $correctionDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        TableDataProvider $tableDataProvider,
        ServiceAccountingCorrectionDataProvider $correctionDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->tableDataProvider = $tableDataProvider;
        $this->correctionDataProvider = $correctionDataProvider;
    }

    public function setCorrectionForDay(Service $service, \DateTimeImmutable $date, int $download, int $upload): void
    {
        $netflowDataUsage = $this->tableDataProvider->getDataInDay($service, $date);

        $this->setCorrection(
            $service,
            $date,
            $download - ($netflowDataUsage ? $netflowDataUsage->getDownload() : 0),
            $upload - ($netflowDataUsage ? $netflowDataUsage->getUpload() : 0)
        );
    }

    /**
     * @throws ServicePeriodNotFoundException
     */
    public function setCorrectionForPeriod(Service $service, \DateTimeImmutable $firstDayOfPeriod, int $download, int $upload): void
    {
        $period = $this->tableDataProvider->findPeriod($service, $firstDayOfPeriod);
        if (! $period) {
            throw new ServicePeriodNotFoundException('Service period not found.');
        }

        $netflowInPeriodData = $this->tableDataProvider->findDataByPeriodStart($service, $firstDayOfPeriod);

        $netflowDownload = $netflowInPeriodData ? $netflowInPeriodData->getDownload() : 0;
        $netflowUpload = $netflowInPeriodData ? $netflowInPeriodData->getUpload() : 0;

        $correctionDataUsage = $this->correctionDataProvider->getCorrectionDataByPeriod($service, $period);
        $correctionDownload = $correctionDataUsage ? $correctionDataUsage->getDownload() : 0;
        $correctionUpload = $correctionDataUsage ? $correctionDataUsage->getUpload() : 0;

        $firstDayCorrection = $this->correctionDataProvider->getCorrectionDataInDay($service, $firstDayOfPeriod);
        $correctionFirstDayDownload = $firstDayCorrection ? $firstDayCorrection->getDownload() : 0;
        $correctionFirstDayUpload = $firstDayCorrection ? $firstDayCorrection->getUpload() : 0;

        $this->setCorrection(
            $service,
            $firstDayOfPeriod,
            $download - $netflowDownload - $correctionDownload + $correctionFirstDayDownload,
            $upload - $netflowUpload - $correctionUpload + $correctionFirstDayUpload
        );
    }

    public function handleDelete(ServiceAccountingCorrection $serviceAccountingCorrection): void
    {
        $this->entityManager->remove($serviceAccountingCorrection);
        $this->entityManager->flush();
    }

    public function handleEdit(ServiceAccountingCorrection $serviceAccountingCorrection): void
    {
        $this->entityManager->flush();
    }

    public function handleNew(ServiceAccountingCorrection $serviceAccountingCorrection): void
    {
        $this->entityManager->persist($serviceAccountingCorrection);
        $this->entityManager->flush();
    }

    private function setCorrection(Service $service, \DateTimeImmutable $date, int $download, int $upload): void
    {
        $serviceAccountingCorrection = $this->entityManager->getRepository(ServiceAccountingCorrection::class)->findOneBy(
            [
                'service' => $service,
                'date' => $date,
            ]
        );

        if (! $serviceAccountingCorrection) {
            if ($download !== 0 || $upload !== 0) {
                $serviceAccountingCorrection = new ServiceAccountingCorrection();
                $this->setProperties($serviceAccountingCorrection, $service, $date, $download, $upload);
                $this->handleNew($serviceAccountingCorrection);
            }

            return;
        }

        if ($download === 0 && $upload === 0) {
            $this->handleDelete($serviceAccountingCorrection);
        }

        $this->setProperties($serviceAccountingCorrection, $service, $date, $download, $upload);
        $this->handleEdit($serviceAccountingCorrection);
    }

    private function setProperties(
        ServiceAccountingCorrection $serviceAccountingCorrection,
        Service $service,
        \DateTimeImmutable $date,
        int $download,
        int $upload
    ): void {
        $serviceAccountingCorrection->setDownload($download);
        $serviceAccountingCorrection->setUpload($upload);
        $serviceAccountingCorrection->setService($service);
        $serviceAccountingCorrection->setDate($date);
    }
}
