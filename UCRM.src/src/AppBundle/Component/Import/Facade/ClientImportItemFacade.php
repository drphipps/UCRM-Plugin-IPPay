<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Facade;

use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\Import\ServiceImportItem;
use TransactionEventsBundle\TransactionDispatcher;

class ClientImportItemFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    /**
     * @throws ImportException
     */
    public function markClientItemForImport(ClientImportItem $item, bool $doImport): void
    {
        if (! $item->getImport()->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED)) {
            // this message is used in frontend, change translation if needed
            throw new ImportException('Import must finish validation before this action is possible.');
        }

        $this->transactionDispatcher->transactional(
            function () use ($item, $doImport) {
                $item->setDoImport($doImport);
            }
        );
    }

    /**
     * @throws ImportException
     */
    public function markServiceItemForImport(ServiceImportItem $item, bool $doImport): void
    {
        $clientImportItem = $item->getImportItem();
        if (! $clientImportItem->getImport()->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED)) {
            // this message is used in frontend, change translation if needed
            throw new ImportException('Import must finish validation before this action is possible.');
        }

        $this->transactionDispatcher->transactional(
            function () use ($clientImportItem, $item, $doImport) {
                $item->setDoImport($doImport);

                if ($doImport) {
                    $clientImportItem->setDoImport(true);
                } else {
                    $importingAnyServiceItem = false;
                    foreach ($clientImportItem->getServiceItems() as $serviceImportItem) {
                        if ($serviceImportItem->isDoImport()) {
                            $importingAnyServiceItem = true;

                            break;
                        }
                    }

                    if (! $importingAnyServiceItem) {
                        $clientImportItem->setDoImport(false);
                    }
                }
            }
        );
    }
}
