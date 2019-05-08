<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceSuspendCancelEvent;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class CancelSuspendWhenInvoiceIsPaidSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Invoice[]
     */
    private $invoicesToHandle = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        Options $options
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = $options;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceEditEvent::class => 'handleInvoiceEditEvent',
            InvoiceDeleteEvent::class => 'handleInvoiceDeleteEvent',
        ];
    }

    public function handleInvoiceEditEvent(InvoiceEditEvent $event): void
    {
        if (
            $event->getInvoiceBeforeUpdate()->getInvoiceStatus() !== Invoice::PAID
            && in_array(
                $event->getInvoice()->getInvoiceStatus(),
                [Invoice::VOID, Invoice::PAID, Invoice::PROFORMA_PROCESSED],
                true
            )
        ) {
            $invoice = $event->getInvoice();
            $this->invoicesToHandle[$invoice->getId()] = $invoice;
        }
    }

    public function handleInvoiceDeleteEvent(InvoiceDeleteEvent $event): void
    {
        $invoice = $event->getInvoice();
        $this->invoicesToHandle[$invoice->getId()] = $invoice;
    }

    public function preFlush(): void
    {
        $ignoredInvoices = array_keys($this->invoicesToHandle);
        foreach ($this->invoicesToHandle as $invoice) {
            $this->cancelSuspendOnRelatedServices($invoice, $ignoredInvoices);
        }

        $this->invoicesToHandle = [];
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->invoicesToHandle = [];
    }

    private function cancelSuspendOnRelatedServices(Invoice $invoice, array $ignoredInvoices): void
    {
        $invoiceItemServiceRepository = $this->entityManager->getRepository(InvoiceItemService::class);
        $defaultStopServiceDue = $this->options->get(Option::STOP_SERVICE_DUE);
        $defaultStopServiceDueDays = $this->options->get(Option::STOP_SERVICE_DUE_DAYS);

        foreach ($invoice->getInvoiceItems() as $invoiceItem) {
            if (! $invoiceItem instanceof InvoiceItemService) {
                continue;
            }

            $service = $invoiceItem->getService();
            if (! $service) {
                continue;
            }

            $stopReasonId = $service->getStopReason() ? $service->getStopReason()->getId() : null;

            if (
                (
                    $stopReasonId === ServiceStopReason::STOP_REASON_OVERDUE_ID
                    || ($stopReasonId === null && $service->getSuspendedFrom())
                )
                && $invoiceItemServiceRepository->canUnsuspendService(
                    $defaultStopServiceDue,
                    $defaultStopServiceDueDays,
                    $service,
                    $ignoredInvoices
                )
            ) {
                $this->unsuspendService($service);
            }
        }
    }

    private function unsuspendService(Service $service): void
    {
        $serviceBeforeUpdate = clone $service;

        $service->setStopReason(null);
        $service->setSuspendedFrom(null);
        $service->setSuspendPostponedByClient(false);
        $service->getSuspendedByInvoices()->clear();

        $this->eventDispatcher->dispatch(
            ServiceEditEvent::class,
            new ServiceEditEvent($service, $serviceBeforeUpdate)
        );
        $this->eventDispatcher->dispatch(
            ServiceSuspendCancelEvent::class,
            new ServiceSuspendCancelEvent($service, $serviceBeforeUpdate)
        );
    }
}
