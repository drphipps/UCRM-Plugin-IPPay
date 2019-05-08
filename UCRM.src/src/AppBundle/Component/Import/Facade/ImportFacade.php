<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Facade;

use AppBundle\Component\Import\FileManager\ImportFileManager;
use AppBundle\Component\Import\Mapping\CsvDelimiterGuesser;
use AppBundle\Entity\CsvImportMapping;
use AppBundle\Entity\CsvImportStructure;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\User;
use AppBundle\Event\Import\ImportEditEvent;
use AppBundle\Form\Data\CsvUploadData;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ImportFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var ImportFileManager
     */
    private $importFileManager;

    /**
     * @var CsvDelimiterGuesser
     */
    private $csvDelimiterGuesser;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        ImportFileManager $importFileManager,
        CsvDelimiterGuesser $csvDelimiterGuesser
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->importFileManager = $importFileManager;
        $this->csvDelimiterGuesser = $csvDelimiterGuesser;
    }

    public function handleCreate(ImportInterface $import, CsvUploadData $csvUploadData, ?User $user): void
    {
        try {
            $this->transactionDispatcher->transactional(
                function (EntityManagerInterface $entityManager) use ($import, $csvUploadData, $user) {
                    $this->importFileManager->save($import, $csvUploadData->file);
                    $import->setCsvHash($this->importFileManager->getHash($import));

                    $csvImportStructure = $this->getCsvStructure($entityManager, $import, $csvUploadData);
                    $import->setCsvDelimiter($csvImportStructure->getCsvDelimiter());
                    $import->setCsvEnclosure($csvImportStructure->getCsvEnclosure());
                    $import->setCsvEscape($csvImportStructure->getCsvEscape());

                    $import->setCsvHasHeader($csvUploadData->hasHeader);

                    $import->setUser($user);

                    $entityManager->persist($import);
                    $entityManager->persist($csvImportStructure);
                }
            );
        } catch (\Throwable $throwable) {
            $this->importFileManager->delete($import);

            throw $throwable;
        }
    }

    public function handleUpdateWithMapping(
        ImportInterface $import,
        ImportInterface $importBeforeUpdate,
        CsvImportMapping $csvImportMapping
    ): void {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($import, $importBeforeUpdate, $csvImportMapping) {
                $entityManager->persist($csvImportMapping);

                yield new ImportEditEvent($import, $importBeforeUpdate);
            }
        );
    }

    public function handleDelete(ImportInterface $import): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($import) {
                $this->importFileManager->delete($import);

                $entityManager->remove($import);
            }
        );
    }

    /**
     * Auto detect structure:
     * - load structure from DB and use it
     * - if not there, guess and save to DB
     * NO auto detect structure (user configured manually):
     * - load structure from DB and update it
     * - or if not there, save new to DB.
     */
    private function getCsvStructure(
        EntityManagerInterface $entityManager,
        ImportInterface $import,
        CsvUploadData $csvUploadData
    ): CsvImportStructure {
        $csvImportStructure = $entityManager->getRepository(CsvImportStructure::class)->findOneBy(
            [
                'hash' => $import->getCsvHash(),
            ]
        );
        $csvImportStructure = $csvImportStructure ?? new CsvImportStructure();
        $csvImportStructure->setHash($import->getCsvHash());

        if ($csvUploadData->autoDetectStructure && ! $csvImportStructure->getId()) {
            $csvImportStructure->setCsvDelimiter(
                $this->csvDelimiterGuesser->guess($this->importFileManager->get($import))
            );
        } elseif (! $csvUploadData->autoDetectStructure) {
            $csvImportStructure->setCsvDelimiter($csvUploadData->delimiter);
            $csvImportStructure->setCsvEnclosure($csvUploadData->enclosure);
            $csvImportStructure->setCsvEscape($csvUploadData->escape);
        }

        return $csvImportStructure;
    }
}
