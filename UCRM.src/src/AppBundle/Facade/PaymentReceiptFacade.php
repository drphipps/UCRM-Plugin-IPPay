<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Payment;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Service\DownloadFinisher;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use Doctrine\ORM\EntityManagerInterface;
use iio\libmergepdf\Merger;
use TransactionEventsBundle\TransactionDispatcher;

class PaymentReceiptFacade
{
    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var NextFinancialNumberFactory
     */
    private $nextFinancialNumberFactory;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        DownloadFinisher $downloadFinisher,
        PdfHandler $pdfHandler,
        EntityManagerInterface $entityManager,
        Pdf $pdf,
        NextFinancialNumberFactory $nextFinancialNumberFactory,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->downloadFinisher = $downloadFinisher;
        $this->pdfHandler = $pdfHandler;
        $this->entityManager = $entityManager;
        $this->pdf = $pdf;
        $this->nextFinancialNumberFactory = $nextFinancialNumberFactory;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function finishReceiptPdfExport(int $downloadId, array $paymentIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export_receipt.pdf',
            function () use ($paymentIds) {
                $payments = $this->entityManager->getRepository(Payment::class)->getMatchedByIds($paymentIds);

                $pdfs = [];
                foreach ($payments as $payment) {
                    $path = $this->pdfHandler->getFullPaymentReceiptPdfPath($payment);
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

                return $merger->merge();
            }
        );
    }

    public function generateReceiptNumbers(array $paymentIds): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($paymentIds) {
                $payments = $entityManager->getRepository(Payment::class)->findMatchedWithoutReceiptNumber($paymentIds);
                foreach ($payments as $payment) {
                    if (! $payment->getClient()) {
                        continue;
                    }

                    $paymentBeforeUpdate = clone $payment;
                    $organization = $payment->getClient()->getOrganization();
                    $payment->setOrganization($organization);
                    $payment->setReceiptNumber($this->nextFinancialNumberFactory->createReceiptNumber($organization));

                    yield new PaymentEditEvent($payment, $paymentBeforeUpdate);
                }
            }
        );
    }
}
