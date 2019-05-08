<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\CsvImport;
use AppBundle\Event\CsvImport\CsvImportEditEvent;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class CsvImportFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNew(CsvImport $csvImport): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($csvImport) {
                $entityManager->persist($csvImport);
            }
        );
    }

    public function handleEdit(CsvImport $csvImport): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($csvImport) {
                /** @var CsvImport $csvImport */
                $csvImport = $entityManager->merge($csvImport);

                yield new CsvImportEditEvent($csvImport);
            }
        );
    }

    public function handleDelete(CsvImport $csvImport): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($csvImport) {
                $entityManager->remove($csvImport);
            }
        );
    }
}
