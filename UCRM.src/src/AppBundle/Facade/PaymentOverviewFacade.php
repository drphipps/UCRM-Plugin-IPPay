<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Component\Csv\EntityCsvFactory\PaymentCsvFactory;
use AppBundle\Component\Csv\EntityCsvFactory\PaymentQuickBooksCsvFactory;
use AppBundle\Factory\PaymentPdfFactory;
use AppBundle\Service\DownloadFinisher;

class PaymentOverviewFacade
{
    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var PaymentCsvFactory
     */
    private $paymentCsvFactory;

    /**
     * @var PaymentPdfFactory
     */
    private $paymentPdfFactory;

    /**
     * @var PaymentQuickBooksCsvFactory
     */
    private $paymentQuickBooksCsvFactory;

    public function __construct(
        DownloadFinisher $downloadFinisher,
        PaymentCsvFactory $paymentCsvFactory,
        PaymentPdfFactory $paymentPdfFactory,
        PaymentQuickBooksCsvFactory $paymentQuickBooksCsvFactory
    ) {
        $this->downloadFinisher = $downloadFinisher;
        $this->paymentCsvFactory = $paymentCsvFactory;
        $this->paymentPdfFactory = $paymentPdfFactory;
        $this->paymentQuickBooksCsvFactory = $paymentQuickBooksCsvFactory;
    }

    public function finishCsvOverviewExport(int $downloadId, array $paymentIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.csv',
            function () use ($paymentIds) {
                return $this->paymentCsvFactory->create($paymentIds);
            }
        );
    }

    public function finishPdfOverviewExport(int $downloadId, array $paymentIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_overview.pdf',
            function () use ($paymentIds) {
                return $this->paymentPdfFactory->create($paymentIds);
            }
        );
    }

    public function finishQuickBooksCsvExport(int $downloadId, array $paymentIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_payments_quickbooks.csv',
            function () use ($paymentIds) {
                return $this->paymentQuickBooksCsvFactory->create($paymentIds);
            }
        );
    }
}
