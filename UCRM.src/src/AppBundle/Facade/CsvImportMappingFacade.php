<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\CsvImportMapping;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class CsvImportMappingFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNew(CsvImportMapping $csvImportMapping): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($csvImportMapping) {
                $entityManager->persist($csvImportMapping);
            }
        );
    }

    public function handleEdit(CsvImportMapping $csvImportMapping): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) {
            }
        );
    }
}
