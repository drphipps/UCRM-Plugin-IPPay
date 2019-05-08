<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use Doctrine\ORM\EntityManagerInterface;

class TariffPeriodDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getActiveClientCountByPeriod(Tariff $tariff): array
    {
        $result = $this->entityManager->getRepository(TariffPeriod::class)
            ->createQueryBuilder('tp')
            ->addSelect('tp.id AS period_id')
            ->addSelect('COUNT(DISTINCT s.client) AS client_count')
            ->innerJoin('tp.services', 's')
            ->innerJoin('s.client', 'c')
            ->andWhere('tp.tariff = :tariff')
            ->andWhere('s.individualPrice IS NULL')
            ->andWhere('s.status != :ended')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('tariff', $tariff)
            ->setParameter('ended', Service::STATUS_ENDED)
            ->addGroupBy('tp.id')
            ->getQuery()
            ->getResult();

        $clientCounts = [];
        foreach ($result as $item) {
            $clientCounts[$item['period_id']] = $item['client_count'];
        }

        return $clientCounts;
    }
}
