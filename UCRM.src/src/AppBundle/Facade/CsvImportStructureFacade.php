<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\CsvImportStructure;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class CsvImportStructureFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNew(CsvImportStructure $csvImportStructure): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($csvImportStructure) {
                $entityManager->persist($csvImportStructure);
            }
        );
    }

    public function handleEdit(CsvImportStructure $csvImportStructure): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) {
            }
        );
    }
}
