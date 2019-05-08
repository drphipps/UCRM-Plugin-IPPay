<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Facade;

use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Event\Import\ImportEditEvent;
use TransactionEventsBundle\TransactionDispatcher;

/**
 * @property TransactionDispatcher $transactionDispatcher
 */
trait ClientImportStatusUpdateTrait
{
    private function updateImportStatus(ImportInterface $import, int $status): void
    {
        if (! in_array($status, ImportInterface::ORDERED_STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf('Status "%d" is not supported.', $status));
        }

        $this->transactionDispatcher->transactional(
            function () use ($import, $status) {
                $importBeforeUpdate = clone $import;

                $import->setStatus($status);

                yield new ImportEditEvent($import, $importBeforeUpdate);
            }
        );
    }
}
