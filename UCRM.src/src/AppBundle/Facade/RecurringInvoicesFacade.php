<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Event\Invoice\InvoiceAddDraftEvent;
use AppBundle\Event\Invoice\InvoiceDraftApprovedEvent;
use AppBundle\Service\Invoice\InvoiceApprover;
use AppBundle\Service\Invoice\InvoiceBuilderFactory;
use AppBundle\Service\Options;
use AppBundle\Util\Invoicing;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use Psr\Log\LoggerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class RecurringInvoicesFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var InvoiceBuilderFactory
     */
    private $invoiceBuilderFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ObjectPersisterInterface
     */
    private $clientObjectPersister;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var InvoiceApprover
     */
    private $invoiceApprover;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManagerInterface $em,
        InvoiceBuilderFactory $invoiceBuilderFactory,
        LoggerInterface $logger,
        ObjectPersisterInterface $clientObjectPersister,
        TransactionDispatcher $transactionDispatcher,
        InvoiceApprover $invoiceApprover,
        Options $options
    ) {
        $this->em = $em;
        $this->invoiceBuilderFactory = $invoiceBuilderFactory;
        $this->logger = $logger;
        $this->clientObjectPersister = $clientObjectPersister;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->invoiceApprover = $invoiceApprover;
        $this->options = $options;
    }

    /**
     * Invoice generation should be always called using this method, because order is important.
     * Separate services should be handled first to enable service related fees, that belong
     * to a separately invoiced service to be on the related invoice.
     */
    public function processServices(Client $client, \DateTimeImmutable $date): array
    {
        [$createdSeparate, $approvedSeparate] = $this->processSeparateServices($client, $date);
        [$createdNonSeparate, $approvedNonSeparate] = $this->processNonSeparateServices($client, $date);

        $createdDrafts = array_merge($createdSeparate, $createdNonSeparate);
        $approvedDrafts = array_merge($approvedSeparate, $approvedNonSeparate);

        return [$createdDrafts, $approvedDrafts];
    }

    /**
     * @internal
     */
    public function processSeparateServices(Client $client, \DateTimeImmutable $date): array
    {
        $createdDrafts = [];
        $approvedDrafts = [];
        $reindexClients = [];

        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($client, $date, &$createdDrafts, &$approvedDrafts, &$reindexClients) {
                $services = $this->em->getRepository(Service::class)->getSeparatelyInvoicedServicesForInvoicing(
                    $client,
                    $date
                );

                foreach ($services as $service) {
                    // refresh to prevent infinite loop - see UCRM-3070
                    $entityManager->refresh($service);
                    if (! $this->canCreateDraft($service, $date)) {
                        continue;
                    }

                    $client = $service->getClient();
                    $nonSeparateServices = $this->em->getRepository(Service::class)
                        ->getNonSeparatelyInvoicedServicesForInvoicing($date, $client);

                    $builder = $this->invoiceBuilderFactory->create($client);
                    $builder->setDate($date);

                    $builder->addService($service);

                    $fees = $this->em->getRepository(Fee::class)->getClientUninvoicedFees($client);

                    foreach ($fees as $fee) {
                        // Only add the fee if it will not be included on any other invoice.
                        if (
                            $fee->isInvoiced()
                            || (
                                $fee->getService()
                                && $fee->getService()->getId() !== $service->getId()
                                && (
                                    in_array($fee->getService(), $services, true)
                                    || in_array($fee->getService(), $nonSeparateServices, true)
                                )
                            )
                        ) {
                            continue;
                        }

                        $fee->setInvoiced(true);
                        $builder->addFee($fee);
                    }

                    $invoice = $builder->getInvoice();

                    if (! $this->hasServiceItem($invoice)) {
                        $client->removeInvoice($invoice);
                        continue;
                    }

                    $this->em->persist($invoice);
                    $this->logCreatedInvoice(
                        $client,
                        $invoice,
                        [$service],
                        $service->isSendEmailsAutomatically() ?? $this->options->get(Option::SEND_INVOICE_BY_EMAIL)
                    );

                    $reindexClients[$client->getId()] = $client;

                    yield new InvoiceAddDraftEvent($invoice);

                    if ($service->isSendEmailsAutomatically() ?? $this->options->get(Option::SEND_INVOICE_BY_EMAIL)) {
                        $approvedDrafts[] = $invoice;
                        $this->invoiceApprover->approve($invoice);

                        yield new InvoiceDraftApprovedEvent($invoice, null);
                    } else {
                        $createdDrafts[] = $invoice;
                    }
                }
            }
        );

        if ($reindexClients) {
            $this->clientObjectPersister->replaceMany(array_values($reindexClients));
        }

        return [$createdDrafts, $approvedDrafts];
    }

    /**
     * @internal
     */
    public function processNonSeparateServices(Client $client, \DateTimeImmutable $date): array
    {
        $approvedDrafts = [];
        $createdDrafts = [];
        $reindexClients = [];

        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($client, $date, &$createdDrafts, &$approvedDrafts, &$reindexClients) {
                $builder = $this->invoiceBuilderFactory->create($client);
                $builder->setDate($date);

                $services = $this->em->getRepository(Service::class)
                    ->getNonSeparatelyInvoicedServicesForInvoicing($date, $client);

                $invoicedServices = [];
                $approveAutomatically = true;

                foreach ($services as $service) {
                    // refresh to prevent infinite loop - see UCRM-3070
                    $entityManager->refresh($service);
                    if (! $this->canCreateDraft($service, $date)) {
                        continue;
                    }

                    $builder->addService($service);
                    $invoicedServices[] = $service;
                    $approveAutomatically = $approveAutomatically
                        && (
                            $service->isSendEmailsAutomatically()
                            ?? $this->options->get(Option::SEND_INVOICE_BY_EMAIL)
                        );
                }

                if (! $invoicedServices) {
                    return;
                }

                $fees = $this->em->getRepository(Fee::class)->getClientUninvoicedFees($client);

                foreach ($fees as $fee) {
                    if ($fee->isInvoiced()) {
                        continue;
                    }

                    $fee->setInvoiced(true);
                    $builder->addFee($fee);
                }

                $invoice = $builder->getInvoice();

                if (! $this->hasServiceItem($invoice)) {
                    $client->removeInvoice($invoice);

                    return;
                }

                $this->em->persist($invoice);
                $this->logCreatedInvoice($client, $invoice, $invoicedServices, $approveAutomatically);

                $reindexClients[] = $client;

                yield new InvoiceAddDraftEvent($invoice);

                if ($approveAutomatically) {
                    $approvedDrafts[] = $invoice;
                    $this->invoiceApprover->approve($invoice);

                    yield new InvoiceDraftApprovedEvent($invoice, null);
                } else {
                    $createdDrafts[] = $invoice;
                }
            }
        );

        if ($reindexClients) {
            $this->clientObjectPersister->replaceMany($reindexClients);
        }

        return [$createdDrafts, $approvedDrafts];
    }

    private function canCreateDraft(Service $service, \DateTimeImmutable $date): bool
    {
        $nextPeriod = Invoicing::getMaxInvoicedPeriodService($service, $date);
        if (! $nextPeriod['invoicedFrom'] || ! $nextPeriod['invoicedTo']) {
            return false;
        }

        if ($this->em->getRepository(InvoiceItemService::class)->hasDraft($service)) {
            return false;
        }

        return true;
    }

    /**
     * This prevents an obscure case of generating an invoice with just InvoiceItemFee.
     * This could happen if there is some service to be invoiced but in reality does not have any invoiceable periods
     * because it was suspended the whole time and invoicing of suspended periods is disabled in settings.
     */
    private function hasServiceItem(Invoice $invoice): bool
    {
        foreach ($invoice->getInvoiceItems() as $item) {
            if ($item instanceof InvoiceItemService) {
                return true;
            }
        }

        return false;
    }

    private function logCreatedInvoice(
        Client $client,
        Invoice $invoice,
        array $services,
        bool $approveAutomatically
    ): void {
        $serviceIds = array_map(
            function (Service $service) {
                return $service->getId();
            },
            $services
        );

        $this->logger->info(
            sprintf(
                'Created draft invoice for clientId %d with total %f for service(s) %s.%s',
                $client->getId(),
                $invoice->getTotal(),
                implode(', ', $serviceIds),
                $approveAutomatically
                    ? ' (will be approved automatically)'
                    : ''
            )
        );
    }
}
