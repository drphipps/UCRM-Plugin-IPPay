<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\NetFlow\NetFlowInPeriodData;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccountingCorrection;
use Doctrine\ORM\EntityManagerInterface;

class ServiceAccountingCorrectionDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TableDataProvider
     */
    private $tableDataProvider;

    public function __construct(EntityManagerInterface $entityManager, TableDataProvider $tableDataProvider)
    {
        $this->entityManager = $entityManager;
        $this->tableDataProvider = $tableDataProvider;
    }

    public function getCorrectionDataInDay(Service $service, \DateTimeImmutable $date): ?ServiceAccountingCorrection
    {
        return $this->entityManager->getRepository(ServiceAccountingCorrection::class)
            ->findOneBy(
                [
                    'service' => $service,
                    'date' => $date,
                ]
            );
    }

    public function getCorrectionDataByPeriod(Service $service, array $invoicedPeriod): ?NetFlowInPeriodData
    {
        if (! $invoicedPeriod['invoicedFrom'] || ! $invoicedPeriod['invoicedTo']) {
            return null;
        }

        $netflowPeriodData = new NetFlowInPeriodData();
        $netflowPeriodData->setInvoicedFrom(\DateTimeImmutable::createFromMutable($invoicedPeriod['invoicedFrom']));
        $netflowPeriodData->setInvoicedTo(\DateTimeImmutable::createFromMutable($invoicedPeriod['invoicedTo']));

        $periodSums = $this->entityManager->getRepository(ServiceAccountingCorrection::class)
            ->createQueryBuilder('sac')
            ->select('SUM(sac.download) AS download')
            ->addSelect('SUM(sac.upload) AS upload')
            ->where('sac.date >= :from')
            ->andWhere('sac.date <= :to')
            ->andWhere('sac.service = :service')
            ->setParameter('from', $netflowPeriodData->getInvoicedFrom())
            ->setParameter('to', $netflowPeriodData->getInvoicedTo())
            ->setParameter('service', $service)
            ->getQuery()->getSingleResult();

        $netflowPeriodData->setDownload($periodSums['download'] ? (int) $periodSums['download'] : 0);
        $netflowPeriodData->setUpload($periodSums['upload'] ? (int) $periodSums['upload'] : 0);

        return $netflowPeriodData;
    }
}
