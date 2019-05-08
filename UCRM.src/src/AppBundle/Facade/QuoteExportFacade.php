<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Csv\EntityCsvFactory\QuoteCsvFactory;
use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Download;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\User;
use AppBundle\Factory\QuotePdfFactory;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\RabbitMq\Quote\ExportQuoteOverviewMessage;
use AppBundle\RabbitMq\Quote\ExportQuotesMessage;
use AppBundle\Service\DownloadFinisher;
use Doctrine\ORM\EntityManagerInterface;
use iio\libmergepdf\Merger;
use RabbitMqBundle\RabbitMqEnqueuer;

class QuoteExportFacade
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
     * @var QuoteCsvFactory
     */
    private $quoteCsvFactory;

    /**
     * @var QuotePdfFactory
     */
    private $quotePdfFactory;

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
        QuoteCsvFactory $quoteCsvFactory,
        QuotePdfFactory $quotePdfFactory,
        DownloadFinisher $downloadFinisher,
        PdfHandler $pdfHandler
    ) {
        $this->entityManager = $entityManager;
        $this->pdf = $pdf;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->quoteCsvFactory = $quoteCsvFactory;
        $this->quotePdfFactory = $quotePdfFactory;
        $this->downloadFinisher = $downloadFinisher;
        $this->pdfHandler = $pdfHandler;
    }

    public function getMergedPdf(array $ids): string
    {
        $quotes = $this->entityManager->getRepository(Quote::class)->getExportableByIds($ids);

        $pdfs = [];

        foreach ($quotes as $quote) {
            $path = $this->pdfHandler->getFullQuotePdfPath($quote);
            if (! $path) {
                continue;
            }

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

    public function finishPdfExport(int $downloadId, array $quoteIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export.pdf',
            function () use ($quoteIds) {
                return $this->getMergedPdf($quoteIds);
            }
        );
    }

    public function finishPdfOverviewExport(int $downloadId, array $quoteIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.pdf',
            function () use ($quoteIds) {
                return $this->quotePdfFactory->createOverview($quoteIds);
            }
        );
    }

    public function finishCsvOverviewExport(int $downloadId, array $quoteIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.csv',
            function () use ($quoteIds) {
                return $this->quoteCsvFactory->create($quoteIds);
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

        $this->rabbitMqEnqueuer->enqueue(new ExportQuotesMessage($download, $ids));
    }

    public function preparePdfOverviewDownload(string $name, array $ids, User $user): void
    {
        $this->prepareOverviewDownloads($name, $ids, $user, ExportQuoteOverviewMessage::FORMAT_PDF);
    }

    public function prepareCsvOverviewDownload(string $name, array $ids, User $user): void
    {
        $this->prepareOverviewDownloads($name, $ids, $user, ExportQuoteOverviewMessage::FORMAT_CSV);
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

        $this->rabbitMqEnqueuer->enqueue(new ExportQuoteOverviewMessage($download, $ids, $filetype));
    }
}
