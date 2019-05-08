<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Facade;

use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Component\Import\FileManager\ImportFileManager;
use AppBundle\Component\Import\Loader\ClientImportItemToClientLoader;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Event\Client\ClientAddEvent;
use AppBundle\Event\Client\ClientAddImportEvent;
use AppBundle\Event\Import\ImportEditEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceAddImportEvent;
use AppBundle\RabbitMq\Import\SaveClientImportItemMessage;
use AppBundle\RabbitMq\Import\SaveClientImportMessage;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionDispatcher;

class ClientImportSaveFacade
{
    use ClientImportStatusUpdateTrait;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ClientImportItemToClientLoader
     */
    private $clientImportItemToClientLoader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImportFileManager
     */
    private $importFileManager;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ClientImportItemToClientLoader $clientImportItemToClientLoader,
        EntityManagerInterface $entityManager,
        ImportFileManager $importFileManager
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->clientImportItemToClientLoader = $clientImportItemToClientLoader;
        $this->entityManager = $entityManager;
        $this->importFileManager = $importFileManager;
    }

    public function enqueueSave(ClientImport $clientImport): void
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED)) {
            // this message is used in frontend, change translation if needed
            throw new ImportException('The import must be validated before it can be started.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            return;
        }

        $this->updateImportStatus($clientImport, ImportInterface::STATUS_ENQUEUED);
        $this->importFileManager->delete($clientImport);

        $this->rabbitMqEnqueuer->enqueue(new SaveClientImportMessage($clientImport->getId()));
    }

    public function enqueueSaveItems(ClientImport $clientImport): void
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            throw new ImportException('The import itself must be enqueued before the items can be enqueued.');
        }

        if ($clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_ENQUEUEING)) {
            return;
        }

        $this->updateImportStatus($clientImport, ImportInterface::STATUS_ITEMS_ENQUEUEING);

        $items = $this->entityManager->getRepository(ClientImportItem::class)->getItemsForSaving($clientImport);
        $count = count($items);
        foreach ($items as $item) {
            $this->rabbitMqEnqueuer->enqueue(new SaveClientImportItemMessage($item->getId()));
        }

        $this->transactionDispatcher->transactional(
            function () use ($clientImport, $count) {
                $clientImportBeforeUpdate = clone $clientImport;

                $clientImport->setStatus(ImportInterface::STATUS_ITEMS_ENQUEUED);
                $clientImport->setCount($count);

                yield new ImportEditEvent($clientImport, $clientImportBeforeUpdate);
            }
        );
    }

    public function saveItem(ClientImportItem $clientImportItem): void
    {
        $clientImport = $clientImportItem->getImport();
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            throw new ImportException(
                'Items can be saved only for import that was enqueued for saving.'
            );
        }

        if ($clientImport->getStatus() !== ImportInterface::STATUS_SAVING) {
            $this->updateImportStatus($clientImport, ImportInterface::STATUS_SAVING);
        }

        $saveItemFailed = false;

        try {
            $this->transactionDispatcher->transactional(
                function (EntityManagerInterface $entityManager) use ($clientImportItem) {
                    yield from $this->handleSaveItem($entityManager, $clientImportItem);
                }
            );
        } catch (\Throwable $exception) {
            $saveItemFailed = true;

            throw $exception;
        } finally {
            $this->transactionDispatcher->transactional(
                function (EntityManagerInterface $entityManager) use ($clientImport, $saveItemFailed) {
                    if (! $entityManager->contains($clientImport)) {
                        $clientImport = $entityManager->find(ClientImport::class, $clientImport->getId());
                    }

                    yield from $this->handleUpdateImportCount($clientImport, $saveItemFailed);
                }
            );
        }
    }

    private function handleSaveItem(
        EntityManagerInterface $entityManager,
        ClientImportItem $clientImportItem
    ): Generator {
        $client = $this->clientImportItemToClientLoader->load($clientImportItem);
        if (! $client) {
            return;
        }

        $entityManager->persist($client);

        yield new ClientAddEvent($client);
        yield new ClientAddImportEvent($client);

        foreach ($client->getServices() as $service) {
            $entityManager->persist($service);

            yield new ServiceAddEvent($service);
            yield new ServiceAddImportEvent($service);
        }
    }

    private function handleUpdateImportCount(ClientImport $clientImport, bool $saveItemFailed): Generator
    {
        $clientImportBeforeUpdate = clone $clientImport;

        if ($saveItemFailed) {
            $clientImport->incrementFailureCount();
        } else {
            $clientImport->incrementSuccessCount();
        }

        $count = $clientImport->getCountSuccess() + $clientImport->getCountFailure();
        if ($clientImport->getCount() && $count >= $clientImport->getCount()) {
            $clientImport->setStatus(ImportInterface::STATUS_FINISHED);
        }

        yield new ImportEditEvent($clientImport, $clientImportBeforeUpdate);
    }
}
