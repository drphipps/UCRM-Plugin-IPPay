<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\ServiceAccountingView;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class ClientTopTrafficDataProvider
{
    public const TYPE_DOWNLOAD = 'download';
    public const TYPE_UPLOAD = 'upload';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int[] $clients
     */
    public function getTopTraffic(
        string $type,
        \DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $limit,
        array $clients = []
    ): array {
        if ($type !== self::TYPE_DOWNLOAD && $type !== self::TYPE_UPLOAD) {
            throw new \InvalidArgumentException('Type not supported.');
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('c AS client')
            ->addSelect(sprintf('SUM(a.%s) AS traffic', $type))
            ->from(Client::class, 'c')
            ->join('c.services', 's')
            ->join(ServiceAccountingView::class, 'a', Join::WITH, 'a.service = s')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('a.date >= :since')
            ->orderBy('traffic', 'DESC')
            ->groupBy('c.id')
            ->setParameter('since', $from, UtcDateTimeType::NAME)
            ->setMaxResults($limit);

        if ($to) {
            $qb->andWhere('a.date <= :to')
                ->setParameter('to', $to, UtcDateTimeType::NAME);
        }
        if ($clients) {
            $qb->andWhere('c.id IN (:clients)')
                ->setParameter('clients', $clients);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAverage(string $type, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        if ($type !== self::TYPE_DOWNLOAD && $type !== self::TYPE_UPLOAD) {
            throw new \InvalidArgumentException('Type not supported.');
        }

        $utc = new \DateTimeZone('UTC');
        $format = $this->entityManager->getConnection()->getDatabasePlatform()->getDateTimeFormatString();

        $stmt = $this->entityManager->getConnection()->executeQuery(
            strtr(
                '
                    SELECT
                      AVG(summed.traffic)
                    FROM (
                      SELECT
                        SUM(sav.%type%) AS traffic
                      FROM client c
                      INNER JOIN service s ON s.client_id = c.client_id
                      INNER JOIN service_accounting_view sav ON sav.service_id = s.service_id
                      WHERE
                        c.deleted_at IS NULL
                        AND c.is_lead = FALSE
                        AND s.deleted_at IS NULL
                        AND sav.date >= ?
                        AND sav.date <= ?
                      GROUP BY c.client_id
                    ) summed
                ',
                [
                    '%type%' => $type,
                ]
            ),
            [
                $from->setTimezone($utc)->format($format),
                $to->setTimezone($utc)->format($format),
            ]
        );

        $average = $stmt->fetchColumn();

        return $average === false ? 0.0 : (float) $average;
    }
}
