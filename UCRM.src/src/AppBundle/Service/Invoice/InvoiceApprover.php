<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Invoice;

use AppBundle\Component\Invoice\CreditApplier;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Service\Financial\NextFinancialNumberFactory;

class InvoiceApprover
{
    /**
     * @var NextFinancialNumberFactory
     */
    private $nextFinancialNumberFactory;

    /**
     * @var CreditApplier
     */
    private $creditApplier;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        NextFinancialNumberFactory $nextFinancialNumberFactory,
        CreditApplier $creditApplier,
        PdfHandler $pdfHandler
    ) {
        $this->nextFinancialNumberFactory = $nextFinancialNumberFactory;
        $this->creditApplier = $creditApplier;
        $this->pdfHandler = $pdfHandler;
    }

    public function approve(Invoice $invoice): void
    {
        $invoice->setInvoiceNumber(
            $invoice->isProforma()
                ? $this->nextFinancialNumberFactory->createProformaInvoiceNumber($invoice->getOrganization())
                : $this->nextFinancialNumberFactory->createInvoiceNumber($invoice->getOrganization())
        );
        $invoice->setInvoiceStatus(Invoice::UNPAID);

        if ($this->canUseCredit($invoice)) {
            $this->creditApplier->apply($invoice);
        }

        if (
            in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)
            && 0.0 === round($invoice->getAmountToPay(), $invoice->getCurrency()->getFractionDigits())
        ) {
            $invoice->setInvoiceStatus(Invoice::PAID);
            $invoice->setUncollectible(false);
        }

        $this->pdfHandler->saveInvoicePdf($invoice);
    }

    private function canUseCredit(Invoice $invoice): bool
    {
        foreach ($invoice->getInvoiceItems() as $item) {
            if (! $item instanceof InvoiceItemService) {
                continue;
            }

            if ($item->getService() && ! $item->getService()->isUseCreditAutomatically()) {
                return false;
            }
        }

        return true;
    }
}
