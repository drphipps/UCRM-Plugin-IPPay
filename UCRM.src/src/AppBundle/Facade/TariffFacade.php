<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Event\Tariff\TariffEditEvent;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use TransactionEventsBundle\TransactionDispatcher;

class TariffFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManager $em,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->em = $em;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function getAll(): array
    {
        $repository = $this->em->getRepository(Tariff::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    public function setDefaults(Tariff $tariff): void
    {
        foreach (TariffPeriod::PERIODS as $i => $period) {
            $tariff->addPeriod(new TariffPeriod($period, false));
        }

        $tariff->setOrganization($this->em->getRepository(Organization::class)->getSelectedOrAlone());
    }

    public function handleCreate(Tariff $tariff)
    {
        if ($tariff->getFccServiceType() !== Tariff::FCC_SERVICE_TYPE_BUSINESS) {
            $tariff->setMaximumContractualDownstreamBandwidth(null);
            $tariff->setMaximumContractualUpstreamBandwidth(null);
        }

        $this->em->persist($tariff);
        $this->em->flush();
    }

    public function handleUpdate(Tariff $tariff, Tariff $tariffBeforeUpdate)
    {
        $this->transactionDispatcher->transactional(
            function () use ($tariff, $tariffBeforeUpdate) {
                if ($tariff->getFccServiceType() !== Tariff::FCC_SERVICE_TYPE_BUSINESS) {
                    $tariff->setMaximumContractualDownstreamBandwidth(null);
                    $tariff->setMaximumContractualUpstreamBandwidth(null);
                }

                yield new TariffEditEvent($tariff, $tariffBeforeUpdate);
            }
        );
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Tariff::class)
            ->createQueryBuilder('t', 't.id')
            ->addSelect('cc.code as currencyCode')
            ->join('t.organization', 'o')
            ->join('o.currency', 'cc')
            ->leftJoin('t.periods', 'tp')
            ->leftJoin('t.services', 's', Expr\Join::WITH, 's.status IN (:statuses)')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter(
                'statuses',
                [
                    Service::STATUS_ACTIVE,
                    Service::STATUS_SUSPENDED,
                    Service::STATUS_PREPARED,
                    Service::STATUS_PREPARED_BLOCKED,
                ]
            )
            ->groupBy('t.id, cc.id, o.id')
            ->andWhere('t.deletedAt IS NULL');
    }

    public function getGridPostFetchCallback(): callable
    {
        return function ($result) {
            $ids = array_map(
                function (array $row) {
                    return $row[0]->getId();
                },
                $result
            );

            $this->em->getRepository(Tariff::class)->loadRelatedEntities('periods', $ids);
        };
    }

    public function handleDelete(Tariff $tariff): bool
    {
        if (! $this->setDeleted($tariff)) {
            return false;
        }

        $this->em->flush();

        return true;
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $tariffs = $this->em->getRepository(Tariff::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($tariffs);
        $deleted = 0;

        foreach ($tariffs as $tariff) {
            if (! $this->setDeleted($tariff)) {
                continue;
            }

            ++$deleted;
        }

        if ($deleted > 0) {
            $this->em->flush();
        }

        return [$deleted, $count - $deleted];
    }

    private function setDeleted(Tariff $tariff): bool
    {
        $tariff->setDeletedAt(new \DateTime());

        return true;
    }
}
