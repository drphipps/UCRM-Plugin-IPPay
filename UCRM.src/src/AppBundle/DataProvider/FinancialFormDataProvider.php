<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Entity\Product;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tax;
use AppBundle\Service\Financial\FinancialTemplateParametersProvider;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class FinancialFormDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var FinancialTemplateParametersProvider
     */
    private $invoiceTemplateParametersProvider;

    /**
     * @var NextFinancialNumberFactory
     */
    private $nextFinancialNumberFactory;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        EntityManagerInterface $entityManager,
        Formatter $formatter,
        FinancialTemplateParametersProvider $invoiceTemplateParametersProvider,
        NextFinancialNumberFactory $nextFinancialNumberFactory,
        Options $options
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->invoiceTemplateParametersProvider = $invoiceTemplateParametersProvider;
        $this->nextFinancialNumberFactory = $nextFinancialNumberFactory;
        $this->options = $options;
    }

    public function getViewData(
        ArrayCollection $formInvoiceItems,
        Collection $oldItems,
        Client $client,
        FinancialInterface $financial
    ): array {
        $servicePeriods = [];
        $periodItems = $formInvoiceItems->isEmpty()
            ? $oldItems
            : array_merge(
                $oldItems->toArray(),
                $formInvoiceItems->toArray()
            );

        foreach ($periodItems as $oldItem) {
            if (! $oldItem instanceof FinancialItemServiceInterface) {
                continue;
            }

            $service = $oldItem->getOriginalService() ?: $oldItem->getService();
            if (
                array_key_exists($service->getId(), $servicePeriods)
                || ! $oldItem->getInvoicedFrom()
                || ! $oldItem->getInvoicedTo()
            ) {
                continue;
            }

            $lastPeriodEnd = clone $oldItem->getInvoicedTo();
            $since = max(
                $service->getInvoicingStart(),
                $lastPeriodEnd->modify(
                    sprintf(
                        '-%d months',
                        $service->getTariffPeriodMonths() * max(ceil($oldItem->getQuantity()), 6)
                    )
                )
            );
            list($invoicedFromChoices, $invoicedToChoices) = Invoicing::getInvoicedPeriodsForm(
                $service,
                Invoicing::getDateUTC($since),
                $this->formatter
            );

            $invoicedFrom = $oldItem->getInvoicedFrom();
            $invoicedTo = $oldItem->getInvoicedTo();

            // This can happen in rare cases when an invoice is created, then deleted
            // (moving invoicingLastPeriodEnd) and then invoicingPeriodStartDay is changed.
            if (! array_key_exists($invoicedFrom->format('Y-m-d'), $invoicedFromChoices)) {
                $invoicedFromChoices[$invoicedFrom->format('Y-m-d')]
                    = $this->formatter->formatDate($invoicedFrom, Formatter::DEFAULT, Formatter::NONE);
                ksort($invoicedFromChoices);
            }

            // Not sure if something similar can happen for invoicedTo but better be safe than sorry.
            if (! array_key_exists($invoicedTo->format('Y-m-d'), $invoicedToChoices)) {
                $invoicedToChoices[$invoicedTo->format('Y-m-d')]
                    = $this->formatter->formatDate($invoicedTo, Formatter::DEFAULT, Formatter::NONE);
                ksort($invoicedToChoices);
            }

            $servicePeriods[$service->getId()] = [
                'invoicedFromChoices' => $invoicedFromChoices,
                'invoicedToChoices' => $invoicedToChoices,
                'invoicedFrom' => $oldItem->getInvoicedFrom()->format('Y-m-d'),
                'invoicedTo' => $oldItem->getInvoicedTo()->format('Y-m-d'),
            ];
        }

        $products = $this->entityManager->getRepository(Product::class)->findBy(
            [
                'deletedAt' => null,
            ],
            [
                'name' => 'ASC',
            ]
        );
        $productsAssoc = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $productsAssoc[$product->getId()] = $product;
        }

        $fees = $this->entityManager->getRepository(Fee::class)->findBy(
            [
                'client' => $client->getId(),
            ],
            [
                'createdDate' => 'ASC',
            ]
        );
        $feesAssoc = [];
        foreach ($fees as $fee) {
            if (
                $financial instanceof Invoice
                && $fee->getService()
                && $fee->getService()->getStatus() === Service::STATUS_QUOTED
            ) {
                continue;
            }
            $feesAssoc[$fee->getId()] = $fee;
        }

        $taxesData = $this->entityManager->getRepository(Tax::class)->getTaxesData();
        $services = $this->getServices($client, $financial);

        $data = [
            'products' => $productsAssoc,
            'fees' => $feesAssoc,
            'services' => $services,
            'servicePeriods' => $servicePeriods,
            'taxesData' => $taxesData,
            'client' => $client,
            'data' => array_merge(
                $this->invoiceTemplateParametersProvider->getParametersClient($financial),
                $this->invoiceTemplateParametersProvider->getParametersOrganization($financial)
            ),
            'roundItems' => $financial->getItemRounding() === FinancialInterface::ITEM_ROUNDING_STANDARD,
            'hasBillingEmail' => $client->hasBillingEmail(),
            'isProformaInvoiceEnabled' => $client->getGenerateProformaInvoices()
                ?? $this->options->get(Option::GENERATE_PROFORMA_INVOICES),
        ];

        if ($financial instanceof Invoice) {
            $data['invoice'] = $financial;
            $data['nextProformaInvoiceNumber'] = $this->nextFinancialNumberFactory->createProformaInvoiceNumber(
                $client->getOrganization()
            );
            $data['nextInvoiceNumber'] = $this->nextFinancialNumberFactory->createInvoiceNumber(
                $client->getOrganization()
            );
        } elseif ($financial instanceof Quote) {
            $data['quote'] = $financial;
        }

        return $data;
    }

    private function getServices(Client $client, FinancialInterface $financial): array
    {
        $services = [
            'regular' => [],
            'obsolete' => [],
            'deferred' => [],
        ];

        foreach ($client->getServices() as $service) {
            if (($financial instanceof Invoice && $service->getStatus() === Service::STATUS_QUOTED)
                || ($financial instanceof Quote && $service->getStatus() !== Service::STATUS_QUOTED)
            ) {
                continue;
            }

            [$periodsStart, $periodsEnd] = Invoicing::getServiceInvoiceablePeriods($service, null);
            // skip if no such periods
            if (! array_filter($periodsStart)) {
                continue;
            }

            $lastPeriodEnd = DateTimeFactory::createDate(max($periodsEnd));
            // skip if no recent invoiceable periods
            if ($lastPeriodEnd->modify(
                    sprintf('+%d months', Invoicing::getPastMonthsWindow($service))
                ) < new \DateTime()
            ) {
                continue;
            }

            if (! $service->isDeleted()) {
                $services['regular'][] = $service;
            } elseif ($service->getStatus() === Service::STATUS_OBSOLETE) {
                $services['obsolete'][] = $service;
            } elseif ($service->getStatus() === Service::STATUS_DEFERRED) {
                $services['deferred'][] = $service;
            }
        }

        return $services;
    }
}
