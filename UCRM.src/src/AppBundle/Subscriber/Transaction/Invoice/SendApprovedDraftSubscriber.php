<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Invoice\InvoiceDraftApprovedEvent;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\RabbitMq\Invoice\SendInvoiceMessage;
use Ds\Queue;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class SendApprovedDraftSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var InvoiceFacade
     */
    private $invoiceFacade;

    /**
     * @var Queue|Invoice[]
     */
    private $invoiceQueue;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer, InvoiceFacade $invoiceFacade)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->invoiceFacade = $invoiceFacade;

        $this->invoiceQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceDraftApprovedEvent::class => 'handleInvoiceDraftApprovedEvent',
        ];
    }

    public function handleInvoiceDraftApprovedEvent(InvoiceDraftApprovedEvent $event): void
    {
        $this->invoiceQueue->push($event->getInvoice());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->invoiceQueue as $invoice) {
            if ($this->invoiceFacade->isPossibleToSendInvoiceAutomatically($invoice)) {
                $this->rabbitMqEnqueuer->enqueue(new SendInvoiceMessage($invoice));
            }
        }
    }

    public function rollback(): void
    {
        $this->invoiceQueue->clear();
    }
}
