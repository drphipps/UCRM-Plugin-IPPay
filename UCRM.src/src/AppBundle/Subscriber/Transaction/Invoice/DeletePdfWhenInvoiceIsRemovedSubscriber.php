<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use Ds\Queue;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeletePdfWhenInvoiceIsRemovedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Queue|Invoice[]
     */
    private $invoices;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->invoices = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceDeleteEvent::class => 'handleInvoiceDeleteEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
        ];
    }

    public function handleInvoiceDeleteEvent(InvoiceDeleteEvent $event): void
    {
        $this->invoices->push($event->getInvoice());
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $event): void
    {
        foreach ($event->getClient()->getInvoices() as $invoice) {
            $this->invoices->push($invoice);
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
        $filesystem = new Filesystem();

        foreach ($this->invoices as $invoice) {
            $path = $invoice->getPdfPath();

            if (! $path) {
                continue;
            }

            try {
                $filesystem->remove($this->rootDir . $path);
            } catch (IOException $e) {
                // Silently ignore.
            }
        }
    }

    public function rollback(): void
    {
        $this->invoices->clear();
    }
}
