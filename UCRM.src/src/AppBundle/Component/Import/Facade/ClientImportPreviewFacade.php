<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Facade;

use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Component\Import\Loader\ClientImportItemsLoader;
use AppBundle\Component\Import\Validator\ClientImportValidator;
use AppBundle\Entity\Import\ClientErrorSummary;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ClientImportItemValidationErrors;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\Import\ServiceImportItem;
use AppBundle\Entity\Import\ServiceImportItemValidationErrors;
use AppBundle\Event\Import\ImportEditEvent;
use AppBundle\RabbitMq\Import\ValidateClientImportMessage;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionDispatcher;

class ClientImportPreviewFacade
{
    use ClientImportStatusUpdateTrait;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var ClientImportItemsLoader
     */
    private $clientImportItemsLoader;

    /**
     * @var ClientImportValidator
     */
    private $clientImportValidator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        ClientImportItemsLoader $clientImportItemsLoader,
        ClientImportValidator $clientImportValidator,
        EntityManagerInterface $entityManager,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->clientImportItemsLoader = $clientImportItemsLoader;
        $this->clientImportValidator = $clientImportValidator;
        $this->entityManager = $entityManager;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    /**
     * @throws ImportException
     */
    public function loadItemsToDatabase(ClientImport $clientImport): void
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_MAPPED)) {
            throw new ImportException('Import must be mapped first.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_LOADING)) {
            return;
        }

        $this->cleanupBeforeDatabaseLoad($clientImport);

        $this->updateImportStatus($clientImport, ImportInterface::STATUS_ITEMS_LOADING);

        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clientImport) {
                $clientImportBeforeUpdate = clone $clientImport;

                $batchCounter = 0;
                foreach ($this->clientImportItemsLoader->load($clientImport) as $importItem) {
                    $entityManager->persist($importItem);

                    if (++$batchCounter > 100) {
                        $entityManager->flush();
                        // Clear of these entities is fine, as they're not used in any subscribers.
                        $entityManager->clear(ClientImportItem::class);
                        $entityManager->clear(ServiceImportItem::class);

                        $batchCounter = 0;
                    }
                }

                $clientImport->setStatus(ImportInterface::STATUS_ITEMS_LOADED);

                yield new ImportEditEvent($clientImport, $clientImportBeforeUpdate);
            }
        );
    }

    public function enqueueRevalidation(ClientImport $clientImport): void
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_LOADED)) {
            // this message is used in frontend, change translation if needed
            throw new ImportException('Import must finish loading before this action is possible.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            // this message is used in frontend, change translation if needed
            throw new ImportException('The import process is already started, this action is no longer possible.');
        }

        $this->updateImportStatus($clientImport, ImportInterface::STATUS_ITEMS_LOADED);

        $this->rabbitMqEnqueuer->enqueue(new ValidateClientImportMessage($clientImport->getId()));
    }

    public function validateItems(ClientImport $clientImport): void
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_LOADED)) {
            throw new ImportException('Import must finish loading before this action is possible.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            throw new ImportException('The import process is already started, this action is no longer possible.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATING)) {
            return;
        }

        $this->cleanupBeforeValidation($clientImport);

        $this->updateImportStatus($clientImport, ImportInterface::STATUS_ITEMS_VALIDATING);

        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($clientImport) {
                $clientImportBeforeUpdate = clone $clientImport;

                if ($clientImport->getErrorSummary()) {
                    $entityManager->remove($clientImport->getErrorSummary());
                    $clientImport->setErrorSummary(null);
                }

                $batchCounter = 0;
                foreach ($this->clientImportValidator->validate($clientImport) as $entity) {
                    $entityManager->persist($entity);

                    if (++$batchCounter > 100) {
                        $entityManager->flush();
                        // Clear of these entities is fine, as they're not used in any subscribers.
                        $entityManager->clear(ClientImportItemValidationErrors::class);
                        $entityManager->clear(ServiceImportItemValidationErrors::class);
                        $entityManager->clear(ClientErrorSummary::class);

                        $batchCounter = 0;
                    }
                }

                $clientImport->setStatus(ImportInterface::STATUS_ITEMS_VALIDATED);

                yield new ImportEditEvent($clientImport, $clientImportBeforeUpdate);
            }
        );
    }

    /**
     * Deletes validation errors from previous validations if there are any.
     * Has to be fast so ORM is not used.
     *
     * This is to handle re-validation, for example if missing tax is added.
     */
    private function cleanupBeforeValidation(ClientImport $clientImport): void
    {
        $this->entityManager->getConnection()->executeQuery(
            '
              DELETE FROM
                client_import_item_validation_errors
              WHERE client_import_item_id IN (
                SELECT id
                FROM client_import_item
                WHERE import_id = :importId
              )
            ',
            [
                'importId' => $clientImport->getId(),
            ]
        );

        $this->entityManager->getConnection()->executeQuery(
            '
              DELETE FROM
                service_import_item_validation_errors
              WHERE service_import_item_id IN (
                SELECT sii.id
                FROM service_import_item sii
                JOIN client_import_item cii ON cii.id = sii.import_item_id
                WHERE cii.import_id = :importId
              )
            ',
            [
                'importId' => $clientImport->getId(),
            ]
        );

        if ($clientImport->getErrorSummary()) {
            $this->transactionDispatcher->transactional(
                function (EntityManagerInterface $entityManager) use ($clientImport) {
                    $entityManager->remove($clientImport->getErrorSummary());
                    $clientImport->setErrorSummary(null);
                }
            );
        }

        $this->entityManager->refresh($clientImport);
    }

    /**
     * Deletes items from previous database loads if there are any.
     * Has to be fast so ORM is not used.
     *
     * This is to handle re-mapping, for example when user goes back from preview and changes columns
     * in mapping.
     */
    private function cleanupBeforeDatabaseLoad(ClientImport $clientImport): void
    {
        $this->entityManager->getConnection()->executeQuery(
            'DELETE FROM client_import_item WHERE import_id = :importId',
            [
                'importId' => $clientImport->getId(),
            ]
        );

        $this->entityManager->refresh($clientImport);
    }
}
