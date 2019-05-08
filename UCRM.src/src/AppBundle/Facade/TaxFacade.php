<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tax;
use AppBundle\Event\Tax\TaxAddEvent;
use AppBundle\Event\Tax\TaxDeleteEvent;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use TransactionEventsBundle\TransactionDispatcher;

class TaxFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(EntityManager $em, TransactionDispatcher $transactionDispatcher)
    {
        $this->em = $em;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function getAllTaxes(): array
    {
        $repository = $this->em->getRepository(Tax::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Tax::class)
            ->createQueryBuilder('t')
            ->andWhere('t.deletedAt IS NULL');
    }

    public function restrictToOneTax(): void
    {
        $this->em->createQueryBuilder()
            ->update(Service::class, 's')
            ->set('s.tax1', 'COALESCE(IDENTITY(s.tax1), IDENTITY(s.tax2), IDENTITY(s.tax3))')
            ->set('s.tax2', ':null')
            ->set('s.tax3', ':null')
            ->setParameter('null', null)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->update(Client::class, 'c')
            ->set('c.tax1', 'COALESCE(IDENTITY(c.tax1), IDENTITY(c.tax2), IDENTITY(c.tax3))')
            ->set('c.tax2', ':null')
            ->set('c.tax3', ':null')
            ->setParameter('null', null)
            ->getQuery()
            ->execute();
    }

    public function handleCreate(Tax $tax): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($tax) {
                $this->em->persist($tax);

                yield new TaxAddEvent($tax);
            }
        );
    }

    public function handleUpdate(Tax $tax): void
    {
        $this->em->flush();
    }

    /**
     * @throws ForeignKeyConstraintViolationException
     */
    public function handleDelete(Tax $tax): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($tax) {
                $this->em->remove($tax);

                yield new TaxDeleteEvent($tax);
            }
        );
    }

    public function handleSetDefault(Tax $tax, bool $selected): void
    {
        $qb = $this->em
            ->getRepository(Tax::class)
            ->createQueryBuilder('t')
            ->update()
            ->set('t.selected', $selected ? 'true' : 'false')
            ->where('t.id = :tax_id')
            ->setParameter('tax_id', $tax->getId());

        $qb->getQuery()->execute();
    }

    public function handleReplaceTax(Tax $tax, Tax $obsoleteTax): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($tax, $obsoleteTax) {
                $tax->setSelected($obsoleteTax->getSelected());
                $this->em->persist($tax);

                $obsoleteTax->setDeletedAt(new \DateTime());
                $obsoleteTax->setSelected(false);

                yield new TaxAddEvent($tax, $obsoleteTax);
            }
        );
    }
}
