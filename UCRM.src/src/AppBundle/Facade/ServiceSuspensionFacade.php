<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServicePostponeEvent;
use AppBundle\Event\Service\ServiceSuspendCancelEvent;
use AppBundle\Event\Service\ServiceSuspendEvent;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceSuspensionFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var Options
     */
    private $options;

    public function __construct(TransactionDispatcher $transactionDispatcher, Options $options)
    {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->options = $options;
    }

    public function suspendService(
        Service $service,
        ServiceStopReason $stopReason,
        \DateTimeImmutable $suspendSince
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($service, $stopReason, $suspendSince) {
                $serviceBeforeUpdate = clone $service;

                $service->setStopReason($stopReason);
                $service->setSuspendedFrom(DateTimeFactory::createFromInterface($suspendSince));
                $service->setSuspendPostponedByClient(false);

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                yield new ServiceSuspendEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    /**
     * Cancels service suspension and prevents the causing invoices to cause automatic suspension again.
     */
    public function manuallyCancelSuspension(Service $service): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($service) {
                $serviceBeforeUpdate = clone $service;

                $service->setStopReason(null);
                $service->setSuspendPostponedByClient(false);
                $service->setSuspendedFrom(null);
                $service->getSuspendedByInvoices()->clear();

                $invoiceItemServiceRepository = $entityManager->getRepository(InvoiceItemService::class);
                $invoiceItems = $invoiceItemServiceRepository->getInvoiceItemsCausingSuspensionForService(
                    $this->options->get(Option::STOP_SERVICE_DUE),
                    $this->options->get(Option::STOP_SERVICE_DUE_DAYS),
                    $service
                );

                foreach ($invoiceItems as $invoiceItem) {
                    $invoiceItem->getInvoice()->setCanCauseSuspension(false);
                }

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                yield new ServiceSuspendCancelEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    public function postponeServiceByClient(Service $service): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($service) {
                $serviceBeforeUpdate = clone $service;

                $service->setStopReason(null);
                $service->setSuspendPostponedByClient(true);
                $suspendedFrom = (new \DateTime())->modify('+24 hours');
                $service->setSuspendedFrom($suspendedFrom);

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                yield new ServicePostponeEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    public function postponeServiceByAdmin(Service $service, \DateTime $postponeUntil): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($service, $postponeUntil) {
                $serviceBeforeUpdate = clone $service;

                $service->setStopReason(null);
                $service->setSuspendPostponedByClient(false);
                $service->setSuspendedFrom($postponeUntil);

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                yield new ServicePostponeEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    /**
     * @param Service[] $services
     *
     * @throws \Throwable
     */
    public function activateServices(array $services): void
    {
        if (! $services) {
            return;
        }
        $this->transactionDispatcher->transactional(
            function () use ($services) {
                foreach ($services as $service) {
                    $serviceBeforeUpdate = clone $service;

                    $service->setStopReason(null);
                    $service->setSuspendPostponedByClient(false);
                    $service->setSuspendedFrom(null);
                    $service->getSuspendedByInvoices()->clear();

                    yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                    yield new ServiceSuspendCancelEvent($service, $serviceBeforeUpdate);
                }
            }
        );
    }
}
