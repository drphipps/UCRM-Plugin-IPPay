<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Csv\EntityCsvFactory\InvoiceCsvFactory;
use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Download;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\User;
use AppBundle\Factory\InvoicePdfFactory;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\RabbitMq\Invoice\ExportInvoiceOverviewMessage;
use AppBundle\RabbitMq\Invoice\ExportInvoicesMessage;
use AppBundle\Service\DownloadFinisher;
use Doctrine\ORM\EntityManagerInterface;
use iio\libmergepdf\Merger;
use RabbitMqBundle\RabbitMqEnqueuer;

class InvoiceExportFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var InvoiceCsvFactory
     */
    private $invoiceCsvFactory;

    /**
     * @var InvoicePdfFactory
     */
    private $invoicePdfFactory;

    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        Pdf $pdf,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        InvoiceCsvFactory $invoiceCsvFactory,
        InvoicePdfFactory $invoicePdfFactory,
        DownloadFinisher $downloadFinisher,
        PdfHandler $pdfHandler
    ) {
        $this->entityManager = $entityManager;
        $this->pdf = $pdf;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->invoiceCsvFactory = $invoiceCsvFactory;
        $this->invoicePdfFactory = $invoicePdfFactory;
        $this->downloadFinisher = $downloadFinisher;
        $this->pdfHandler = $pdfHandler;
    }

    public function getMergedPdf(array $ids): string
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->getExportableByIds($ids);

        $pdfs = [];

        foreach ($invoices as $invoice) {
            $path = $this->pdfHandler->getFullInvoicePdfPath($invoice);
            if (! $path) {
                continue;
            }

            $invoice->setPdfBatchPrinted(true);
            $pdfs[] = $path;
        }

        if (! $pdfs) {
            return $this->pdf->generateFromHtml('');
        }

        $merger = new Merger();
        $merger->addIterator($pdfs);
        $pdf = $merger->merge();

        $this->entityManager->flush();

        return $pdf;
    }

    public function finishPdfExport(int $downloadId, array $invoiceIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export.pdf',
            function () use ($invoiceIds) {
                return $this->getMergedPdf($invoiceIds);
            }
        );
    }

    public function finishPdfOverviewExport(int $downloadId, array $invoiceIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.pdf',
            function () use ($invoiceIds) {
                return $this->invoicePdfFactory->createOverview($invoiceIds);
            }
        );
    }

    public function finishCsvOverviewExport(int $downloadId, array $invoiceIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.csv',
            function () use ($invoiceIds) {
                return $this->invoiceCsvFactory->create($invoiceIds);
            }
        );
    }

    public function preparePdfDownload(string $name, array $ids, User $user): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportInvoicesMessage($download, $ids));
    }

    public function preparePdfOverviewDownload(string $name, array $ids, User $user): void
    {
        $this->prepareOverviewDownloads($name, $ids, $user, ExportInvoiceOverviewMessage::FORMAT_PDF);
    }

    public function prepareCsvOverviewDownload(string $name, array $ids, User $user): void
    {
        $this->prepareOverviewDownloads($name, $ids, $user, ExportInvoiceOverviewMessage::FORMAT_CSV);
    }

    private function prepareOverviewDownloads(string $name, array $ids, User $user, string $filetype): void
    {
        $download = new Download();

        $this->entityManager->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->entityManager->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportInvoiceOverviewMessage($download, $ids, $filetype));
    }
}
