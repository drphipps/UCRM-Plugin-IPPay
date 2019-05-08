<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateClientOverdueBadgeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceAddEvent::class => 'handleInvoiceAddEvent',
            InvoiceEditEvent::class => 'handleInvoiceEditEvent',
            InvoiceDeleteEvent::class => 'handleInvoiceDeleteEvent',
        ];
    }

    public function handleInvoiceAddEvent(InvoiceAddEvent $event): void
    {
        $this->updateClientBadgeAddEdit($event->getInvoice());
    }

    public function handleInvoiceEditEvent(InvoiceEditEvent $event): void
    {
        $this->updateClientBadgeAddEdit($event->getInvoice());
    }

    public function handleInvoiceDeleteEvent(InvoiceDeleteEvent $event): void
    {
        $client = $event->getInvoice()->getClient();

        foreach ($client->getInvoices() as $invoice) {
            if ($invoice->isOverdue()) {
                $client->setHasOverdueInvoice(true);

                return;
            }
        }

        $client->setHasOverdueInvoice(false);
    }

    private function updateClientBadgeAddEdit(Invoice $eventInvoice): void
    {
        $client = $eventInvoice->getClient();
        if ($eventInvoice->isOverdue()) {
            $client->setHasOverdueInvoice(true);

            return;
        }

        foreach ($client->getInvoices() as $invoice) {
            if ($invoice->isOverdue()) {
                $client->setHasOverdueInvoice(true);

                return;
            }
        }

        $client->setHasOverdueInvoice(false);
    }
}
