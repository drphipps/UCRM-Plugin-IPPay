<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Invoice\AbstractInvoiceEvent;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\RabbitMq\Invoice\GenerateInvoiceFromProformaMessage;
use Ds\Queue;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class CreatePaidInvoiceFromProformaSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|Invoice[]
     */
    private $proformaInvoices;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->proformaInvoices = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceEditEvent::class => 'handleCreateInvoice',
            InvoiceAddEvent::class => 'handleCreateInvoice',
        ];
    }

    public function handleCreateInvoice(AbstractInvoiceEvent $event): void
    {
        if (! $this->canCreate($event)) {
            return;
        }

        $this->proformaInvoices->push($event->getInvoice());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->proformaInvoices as $proformaInvoice) {
            $this->rabbitMqEnqueuer->enqueue(new GenerateInvoiceFromProformaMessage($proformaInvoice->getId()));
        }
    }

    public function rollback(): void
    {
        $this->proformaInvoices->clear();
    }

    private function canCreate(AbstractInvoiceEvent $event): bool
    {
        $isPaidProforma = $event->getInvoice()->isProforma()
            && $event->getInvoice()->getInvoiceStatus() === Invoice::PAID;

        if ($event instanceof InvoiceEditEvent) {
            return $isPaidProforma && $event->getInvoiceBeforeUpdate()->getInvoiceStatus() !== Invoice::PAID;
        }

        return $isPaidProforma;
    }
}
