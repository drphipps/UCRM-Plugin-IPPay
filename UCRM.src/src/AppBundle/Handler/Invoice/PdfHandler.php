<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\Invoice;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\Financial\FinancialTemplateInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Service\Options;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings as NStrings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionDispatcher;

class PdfHandler
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var FinancialTemplateRenderer
     */
    private $financialTemplateRenderer;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var HeaderNotifier
     */
    private $headerNotifier;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Options $options,
        FinancialTemplateRenderer $financialTemplateRenderer,
        Pdf $pdf,
        HeaderNotifier $headerNotifier,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        TransactionDispatcher $transactionDispatcher,
        string $rootDir
    ) {
        $this->options = $options;
        $this->financialTemplateRenderer = $financialTemplateRenderer;
        $this->pdf = $pdf;
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->rootDir = $rootDir;
        $this->filesystem = new Filesystem();
    }

    public function saveInvoicePdf(Invoice $invoice): void
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            throw new \InvalidArgumentException('Can\'t generate invoice until migration is complete.');
        }

        if ($invoice->isProforma()) {
            $invoiceTemplate = $invoice->getProformaInvoiceTemplate();
        } else {
            $invoiceTemplate = $invoice->getInvoiceTemplate();
        }

        try {
            $pdf = $this->pdf->generateFromHtml(
                $this->financialTemplateRenderer->renderInvoice($invoice, $invoiceTemplate),
                $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER)
            );

            $invoice->setPdfPath($this->generatePdfPath($invoice));

            $this->filesystem->dumpFile($this->rootDir . $invoice->getPdfPath(), $pdf);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            $this->handleInvoiceTemplateException($invoiceTemplate, $exception);
        }
    }

    /**
     * Returns full path to invoice PDF and regenerates the file if it should exist, but does not.
     */
    public function getFullInvoicePdfPath(Invoice $invoice, bool $forceRegenerate = false): ?string
    {
        $pdfPath = $invoice->getPdfPath();
        if (! $pdfPath) {
            return null;
        }

        $invoiceBeforeUpdate = clone $invoice;

        $fullPdfPath = $this->rootDir . $pdfPath;
        // first check for path with organization ID, then without it to provide backward compatibility
        $pdfPathCheck = NStrings::replace(
            $pdfPath,
            sprintf(
                $invoice->isProforma() ? '~^/data/proforma-invoices/%d/~' : '~^/data/invoices/%d/~',
                $invoice->getOrganization()->getId()
            ),
            ''
        );
        $pdfPathCheck = NStrings::replace(
            $pdfPathCheck,
            $invoice->isProforma() ? '~^/data/proforma-invoices/%d/~' : '~^/data/invoices/~',
            ''
        );
        if ((
                NStrings::contains($pdfPathCheck, '/')
                || NStrings::contains($pdfPathCheck, '\\')
            )
            && $this->filesystem->exists($fullPdfPath)
        ) {
            $this->filesystem->remove($fullPdfPath);
        }

        if ($forceRegenerate || ! $this->filesystem->exists($fullPdfPath)) {
            $this->saveInvoicePdf($invoice);
            $previousFullPdfPath = $fullPdfPath;
            $fullPdfPath = $this->rootDir . $invoice->getPdfPath();

            if ($forceRegenerate) {
                if ($fullPdfPath !== $previousFullPdfPath) {
                    $this->filesystem->remove($previousFullPdfPath);
                }

                $this->transactionDispatcher->transactional(
                    function () use ($invoice, $invoiceBeforeUpdate) {
                        yield new InvoiceEditEvent($invoice, $invoiceBeforeUpdate);
                    }
                );
            }
        }

        return $fullPdfPath;
    }

    private function handleInvoiceTemplateException(
        FinancialTemplateInterface $invoiceTemplate,
        \Exception $exception
    ): void {
        if ($invoiceTemplate->isErrorNotificationSent()) {
            throw $exception;
        }

        $this->headerNotifier->sendToAllAdmins(
            HeaderNotification::TYPE_DANGER,
            $this->translator->trans('Invoice PDF failed to generate.'),
            strtr(
                $this->translator->trans(
                    'PDF for invoice could not be generated due to an error in invoice template. Error message: "%errorMessage%"'
                ),
                [
                    '%errorMessage%' => $exception->getMessage(),
                ]
            )
        );
        $invoiceTemplate->setIsValid(false);
        $invoiceTemplate->setErrorNotificationSent(true);
        $this->entityManager->flush($invoiceTemplate);

        throw $exception;
    }

    private function generatePdfPath(Invoice $invoice): string
    {
        return sprintf(
            $invoice->isProforma() ? '/data/proforma-invoices/%d/%s.pdf' : '/data/invoices/%d/%s.pdf',
            $invoice->getOrganization()->getId(),
            Strings::sanitizeFileName($invoice->getInvoiceNumber())
        );
    }
}
