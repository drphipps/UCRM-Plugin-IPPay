<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class ClientLogsViewDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGridModel(?Client $client = null, array $filters = []): QueryBuilder
    {
        $qb = $this->em->getRepository(ClientLogsView::class)
            ->createQueryBuilder('el')
            ->addSelect('c')
            ->addSelect('el.logId AS el_log_id')
            ->leftJoin('el.client', 'c')
            ->addOrderBy('el.createdDate', 'DESC');

        if ($client) {
            $qb->andWhere('c.id = :clientId')
                ->setParameter(':clientId', $client->getId());
        }

        foreach ($filters as $identifier => $value) {
            $qb->andWhere('el.' . $identifier . ' IN (' . ':' . $identifier . ')')
                ->setParameter(':' . $identifier, $value);
        }

        return $qb;
    }

    public function getByDate(
        Client $client,
        array $filters,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to
    ): array {
        $qb = $this->getGridModel($client, $filters);
        $qb->select('el, c');

        if ($from) {
            $qb->andWhere('el.createdDate >= :from')
                ->setParameter('from', $from, UtcDateTimeType::NAME);
        }

        if ($to) {
            $qb->andWhere('el.createdDate < :to')
                ->setParameter('to', $to, UtcDateTimeType::NAME);
        }

        $logs = $qb->getQuery()->getResult();
        $postFetch = $this->getGridPostFetchCallback();
        $postFetch($logs);

        return $logs;
    }

    public function getByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return $this->em->getRepository(ClientLogsView::class)->getByIds($ids);
    }

    public function getGridPostFetchCallback(): \Closure
    {
        return function ($result) {
            $entityLogIds = [];
            $emailLogEntities = [];
            foreach ($result as $row) {
                if ($row instanceof ClientLogsView) {
                    $entity = $row;
                } else {
                    /** @var ClientLogsView $entity */
                    $entity = $row[0];
                }
                switch ($entity->getLogType()) {
                    case ClientLogsView::LOG_TYPE_ENTITY_LOG:
                        $entityLogIds[] = $entity->getLogId();

                        break;
                    case ClientLogsView::LOG_TYPE_EMAIL_LOG:
                        $emailLogEntities[] = $entity->getLogId();

                        break;
                    case ClientLogsView::LOG_TYPE_CLIENT_LOG:

                        break;
                    default:
                        throw new \RuntimeException('Unknown ClientLogsView logType.');
                }
            }

            $this->em->getRepository(EntityLog::class)->findBy(['id' => $entityLogIds]);
            $this->em->getRepository(EmailLog::class)->findBy(['id' => $emailLogEntities]);
        };
    }
}
