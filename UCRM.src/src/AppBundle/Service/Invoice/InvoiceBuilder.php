<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Invoice;

use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Factory\Financial\FinancialItemFeeFactory;
use AppBundle\Factory\Financial\FinancialItemServiceFactory;
use AppBundle\Factory\Financial\FinancialItemSurchargeFactory;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\Options;
use AppBundle\Util\Invoicing;

class InvoiceBuilder
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var FinancialItemServiceFactory
     */
    private $financialItemServiceFactory;

    /**
     * @var FinancialItemSurchargeFactory
     */
    private $financialItemSurchargeFactory;

    /**
     * @var FinancialItemFeeFactory
     */
    private $financialItemFeeFactory;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var \DateTimeImmutable|null
     */
    private $date;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array|Service[]
     */
    private $services = [];

    /**
     * @var array|Fee[]
     */
    private $fees = [];

    public function __construct(
        Options $options,
        FinancialFactory $financialFactory,
        FinancialItemServiceFactory $financialItemServiceFactory,
        FinancialItemSurchargeFactory $financialItemSurchargeFactory,
        FinancialItemFeeFactory $financialItemFeeFactory,
        FinancialTotalCalculator $financialTotalCalculator,
        Client $client
    ) {
        $this->options = $options;
        $this->financialFactory = $financialFactory;
        $this->financialItemServiceFactory = $financialItemServiceFactory;
        $this->financialItemSurchargeFactory = $financialItemSurchargeFactory;
        $this->financialItemFeeFactory = $financialItemFeeFactory;
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->client = $client;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function addService(Service $service): void
    {
        $this->services[] = $service;
    }

    public function addFee(Fee $fee): void
    {
        $this->fees[] = $fee;
    }

    public function getInvoice(): Invoice
    {
        $date = $this->date ?: new \DateTimeImmutable();

        if (
            $this->client->getGenerateProformaInvoices()
            ?? $this->options->get(Option::GENERATE_PROFORMA_INVOICES)
        ) {
            $invoice = $this->financialFactory->createProformaInvoice($this->client, $date);
        } else {
            $invoice = $this->financialFactory->createInvoice($this->client, $date);
        }

        $invoice->setInvoiceNumber(null);
        $invoice->setInvoiceStatus(Invoice::DRAFT);

        $itemPosition = -1;
        foreach ($this->services as $service) {
            $periods = Invoicing::getPeriodsForInvoicing(
                $service,
                $date,
                (bool) $this->options->get(Option::STOP_INVOICING)
            );

            foreach ($periods as $period) {
                $item = $this->financialItemServiceFactory->createInvoiceItem(
                    $service,
                    clone $period['invoicedFrom'],
                    clone $period['invoicedTo']
                );
                $item->setItemPosition(++$itemPosition);
                $item->setInvoice($invoice);
                $invoice->addInvoiceItem($item);

                foreach ($service->getServiceSurcharges() as $surcharge) {
                    $surchargeItem = $this->financialItemSurchargeFactory->createInvoiceItem($surcharge);
                    $surchargeItem->setInvoice($invoice);
                    $surchargeItem->setQuantity($item->getQuantity());
                    $surchargeItem->calculateTotal();
                    $surchargeItem->setItemPosition(++$itemPosition);
                    $invoice->addInvoiceItem($surchargeItem);
                }
            }
        }

        if (count($invoice->getInvoiceItems())) {
            foreach ($this->fees as $fee) {
                $item = $this->financialItemFeeFactory->createInvoiceItem($fee);
                $item->setInvoice($invoice);
                $item->setItemPosition(++$itemPosition);
                $invoice->addInvoiceItem($item);
            }
        }

        $this->financialTotalCalculator->computeTotal($invoice);

        return $invoice;
    }
}
