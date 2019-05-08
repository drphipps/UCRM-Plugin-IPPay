<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Factory\Financial\InvoiceFactory;
use AppBundle\Handler\Invoice\PdfHandler;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ProformaInvoiceFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        InvoiceFactory $invoiceFactory,
        PdfHandler $pdfHandler
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->invoiceFactory = $invoiceFactory;
        $this->pdfHandler = $pdfHandler;
    }

    public function createInvoiceFromProforma(Invoice $proformaInvoice): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($proformaInvoice) {
                $invoice = $this->invoiceFactory->createPaidInvoiceFromProformaInvoice($proformaInvoice);

                $entityManager->persist($invoice);
                $entityManager->persist($invoice->getPaymentToken());

                $this->pdfHandler->saveInvoicePdf($invoice);

                yield new InvoiceAddEvent($invoice);
            }
        );
    }
}
