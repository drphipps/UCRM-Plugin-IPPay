<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Transformer\Financial\FinancialToInvoiceTransformer;

class InvoiceFactory
{
    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var FinancialFactory
     */
    private $financialFactory;

    /**
     * @var InvoiceCalculations
     */
    private $invoiceCalculations;

    /**
     * @var FinancialToInvoiceTransformer
     */
    private $financialToInvoiceTransformer;

    public function __construct(
        FinancialTotalCalculator $financialTotalCalculator,
        PaymentTokenFactory $paymentTokenFactory,
        FinancialFactory $financialFactory,
        InvoiceCalculations $invoiceCalculations,
        FinancialToInvoiceTransformer $financialToInvoiceTransformer
    ) {
        $this->financialTotalCalculator = $financialTotalCalculator;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->financialFactory = $financialFactory;
        $this->invoiceCalculations = $invoiceCalculations;
        $this->financialToInvoiceTransformer = $financialToInvoiceTransformer;
    }

    public function createPaidInvoiceFromProformaInvoice(Invoice $proformaInvoice): Invoice
    {
        if (! $proformaInvoice->isProforma()) {
            throw new \InvalidArgumentException('Given invoice is not a proforma.');
        }

        $invoice = $this->financialFactory->createInvoice($proformaInvoice->getClient(), new \DateTimeImmutable());
        $invoice->setTaxableSupplyDate($proformaInvoice->getTaxableSupplyDate());
        $invoice->setProformaInvoice($proformaInvoice);
        $proformaInvoice->setGeneratedInvoice($invoice);
        $proformaInvoice->setInvoiceStatus(Invoice::PROFORMA_PROCESSED);
        $items = $this->financialToInvoiceTransformer->getInvoiceItemsFromFinancial($proformaInvoice);
        foreach ($items as $item) {
            $item->setInvoice($invoice);
            $invoice->getInvoiceItems()->add($item);
        }

        foreach ($proformaInvoice->getPaymentCovers() as $paymentCover) {
            $invoice->addPaymentCover($paymentCover);
            $paymentCover->setInvoice($invoice);
            $proformaInvoice->removePaymentCover($paymentCover);
        }

        $this->financialTotalCalculator->computeTotal($invoice);
        $this->invoiceCalculations->recalculatePayments($invoice);

        // payment token must be created before PDF is generated
        $this->paymentTokenFactory->create($invoice);

        // update client collection manually to correctly handle subscribers
        $invoice->getClient()->addInvoice($invoice);

        return $invoice;
    }
}
