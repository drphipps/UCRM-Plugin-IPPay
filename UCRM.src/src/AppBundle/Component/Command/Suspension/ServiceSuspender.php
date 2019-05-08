<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Suspension;

use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceSuspendEvent;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceStatusUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceSuspender
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var SynchronizationManager
     */
    private $synchronizationManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        Options $options,
        EntityManagerInterface $entityManager,
        ServiceStatusUpdater $serviceStatusUpdater,
        ClientStatusUpdater $clientStatusUpdater,
        SynchronizationManager $synchronizationManager,
        LoggerInterface $logger,
        ActionLogger $actionLogger,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->options = $options;
        $this->entityManager = $entityManager;
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->synchronizationManager = $synchronizationManager;
        $this->logger = $logger;
        $this->actionLogger = $actionLogger;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function suspend(?Service $service = null): bool
    {
        if (! $this->options->get(Option::SUSPEND_ENABLED)) {
            $this->logger->info('Suspend feature is disabled in system settings');

            return false;
        }

        $countSuspendedServices = $this->suspendServices($service);
        $this->logger->info(sprintf('%d services suspended', $countSuspendedServices));

        if ($countSuspendedServices > 0) {
            $message['logMsg'] = [
                'message' => '%d services suspended.',
                'replacements' => $countSuspendedServices,
            ];
            $this->actionLogger->log($message, null, null, EntityLog::SUSPEND);

            $this->serviceStatusUpdater->updateServices();
            $this->clientStatusUpdater->update();
        }

        return $countSuspendedServices > 0;
    }

    private function suspendServices(?Service $service): int
    {
        $invoiceItemServiceRepository = $this->entityManager->getRepository(InvoiceItemService::class);
        $invoiceItems = $invoiceItemServiceRepository->getOverdueInvoiceItemsForSuspension(
            (bool) $this->options->get(Option::STOP_SERVICE_DUE),
            (int) $this->options->get(Option::STOP_SERVICE_DUE_DAYS),
            $service
        );
        $ids = array_map(
            function (InvoiceItemService $item) {
                return $item->getId();
            },
            $invoiceItems
        );

        $invoiceItemServiceRepository->loadRelatedEntities('service', $ids);
        $invoiceItemServiceRepository->loadRelatedEntities('invoice', $ids);

        $minimumUnpaidAmount = $this->options->get(Option::SUSPENSION_MINIMUM_UNPAID_AMOUNT);

        $services = [];
        $invoices = [];
        foreach ($invoiceItems as $item) {
            $service = $item->getService();
            $invoice = $item->getInvoice();
            if (! $service || ! $invoice) {
                continue;
            }

            $fractionDigits = $invoice->getCurrency()->getFractionDigits();
            if (round($invoice->getAmountToPay(), $fractionDigits) < round($minimumUnpaidAmount, $fractionDigits)) {
                continue;
            }

            $services[$service->getId()] = $service;
            $invoices[$service->getId()][$invoice->getId()] = $invoice;
        }

        return (int) $this->transactionDispatcher->transactional(
            function () use ($services, $invoices) {
                $this->entityManager->getRepository(Service::class)->loadRelatedEntities('client', array_keys($services));
                $this->entityManager->getRepository(Service::class)->loadRelatedEntities(['client', 'user'], array_keys($services));

                $stopReason = $this->entityManager->getRepository(ServiceStopReason::class)->find(
                    ServiceStopReason::STOP_REASON_OVERDUE_ID
                );
                $countSuspendedServices = 0;

                /** @var Service $service */
                foreach ($services as $service) {
                    $serviceBeforeChange = clone $service;

                    /** @var Invoice[] $serviceInvoices */
                    $serviceInvoices = $invoices[$service->getId()];

                    $service->setStopReason($stopReason);
                    if (! $service->getSuspendedFrom()) {
                        $service->setSuspendPostponedByClient(false);
                    }
                    $service->setSuspendedFrom(new \DateTime());
                    $service->getSuspendedByInvoices()->clear();
                    foreach ($serviceInvoices as $invoice) {
                        $service->addSuspendedByInvoice($invoice);
                    }

                    yield new ServiceEditEvent($service, $serviceBeforeChange);
                    yield new ServiceSuspendEvent($service, $serviceBeforeChange);

                    $this->logger->info(sprintf('Service id:%d is suspended now.', $service->getId()));

                    $this->synchronizationManager->unsynchronizeService($service);

                    ++$countSuspendedServices;
                }

                if ($countSuspendedServices > 0) {
                    $this->synchronizationManager->unsynchronizeSuspend();
                }

                return $countSuspendedServices;
            }
        );
    }
}
