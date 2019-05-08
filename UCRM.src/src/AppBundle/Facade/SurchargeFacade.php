<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Surcharge;
use AppBundle\Event\Surcharge\SurchargeAddEvent;
use AppBundle\Event\Surcharge\SurchargeArchiveEvent;
use AppBundle\Event\Surcharge\SurchargeEditEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TransactionEventsBundle\TransactionDispatcher;

class SurchargeFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(EntityManagerInterface $entityManager, TransactionDispatcher $transactionDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function getAllSurcharges(): array
    {
        $repository = $this->entityManager->getRepository(Surcharge::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(Surcharge::class)
            ->createQueryBuilder('s')
            ->andWhere('s.deletedAt IS NULL');
    }

    public function handleCreate(Surcharge $surcharge): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($surcharge) {
                $this->entityManager->persist($surcharge);

                yield new SurchargeAddEvent($surcharge);
            }
        );
    }

    public function handleUpdate(Surcharge $surcharge, Surcharge $surchargeBeforeUpdate): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($surcharge, $surchargeBeforeUpdate) {
                yield new SurchargeEditEvent($surcharge, $surchargeBeforeUpdate);
            }
        );
    }

    public function handleDelete(Surcharge $surcharge): bool
    {
        return $this->transactionDispatcher->transactional(
            function () use ($surcharge) {
                if (! $this->setDeleted($surcharge)) {
                    return false;
                }

                yield new SurchargeArchiveEvent($surcharge);

                return true;
            }
        );
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $surcharges = $this->entityManager->getRepository(Surcharge::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($surcharges);
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function () use ($surcharges, &$deleted) {
                foreach ($surcharges as $surcharge) {
                    if (! $this->setDeleted($surcharge)) {
                        continue;
                    }

                    yield new SurchargeArchiveEvent($surcharge);

                    ++$deleted;
                }
            }
        );

        return [$deleted, $count - $deleted];
    }

    private function setDeleted(Surcharge $surcharge): bool
    {
        $surcharge->setDeletedAt(new \DateTime());

        return true;
    }
}
