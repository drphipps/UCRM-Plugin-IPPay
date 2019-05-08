<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdateProformaWhenInvoiceIsDeletedSubscriber implements TransactionEventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceDeleteEvent::class => 'handleDeleteInvoice',
        ];
    }

    public function handleDeleteInvoice(InvoiceDeleteEvent $event): void
    {
        if ($proformaInvoice = $event->getInvoice()->getProformaInvoice()) {
            $proformaInvoice->setInvoiceStatus(Invoice::UNPAID);
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
    }
}
