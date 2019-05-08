<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\DraftGeneration;
use AppBundle\Event\DraftGeneration\DraftGenerationEditEvent;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class DraftGenerationFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNew(DraftGeneration $draftGeneration): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($draftGeneration) {
                $entityManager->persist($draftGeneration);
            },
            Connection::TRANSACTION_REPEATABLE_READ
        );
    }

    public function handleEdit(DraftGeneration $draftGeneration): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($draftGeneration) {
                yield new DraftGenerationEditEvent($draftGeneration);
            }
        );
    }

    public function handleDelete(DraftGeneration $draftGeneration): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($draftGeneration) {
                $entityManager->remove($draftGeneration);
            }
        );
    }
}
