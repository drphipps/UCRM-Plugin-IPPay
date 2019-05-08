<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\ReportDataUsage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DataUsageDataProvider extends AbstractTrafficDataProvider
{
    public const SINCE_DAY = 'day';
    public const SINCE_WEEK = 'week';
    public const SINCE_MONTH = 'month';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(string $period): QueryBuilder
    {
        [$from, $to] = $this->getPeriod($period);

        $qb = $this->entityManager->getRepository(Client::class)->getTrafficQueryBuilder($from, $to);

        return $qb;
    }

    public function getOverviewGridModel(): QueryBuilder
    {
        $today = new \DateTimeImmutable('midnight');

        return $this->entityManager->getRepository(ReportDataUsage::class)->getTrafficOverviewQueryBuilder($today);
    }
}
