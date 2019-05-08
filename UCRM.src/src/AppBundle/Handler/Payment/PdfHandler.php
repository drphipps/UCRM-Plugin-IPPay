<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\Payment;

use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Factory\PaymentReceiptPdfFactory;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;

class PdfHandler
{
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
     * @var PaymentReceiptPdfFactory
     */
    private $paymentReceiptPdfFactory;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        HeaderNotifier $headerNotifier,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        PaymentReceiptPdfFactory $paymentReceiptPdfFactory,
        string $rootDir
    ) {
        $this->headerNotifier = $headerNotifier;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->paymentReceiptPdfFactory = $paymentReceiptPdfFactory;
        $this->rootDir = $rootDir;
        $this->filesystem = new Filesystem();
    }

    public function savePaymentReceiptPdf(Payment $payment): void
    {
        if (! $payment->getClient()) {
            return;
        }

        $paymentReceiptTemplate = $payment->getClient()->getOrganization()->getPaymentReceiptTemplate();
        try {
            $pdf = $this->paymentReceiptPdfFactory->create($payment);

            $fileName = $payment->getPdfPath();
            if (! $fileName) {
                $fileName = sprintf(
                    '/data/payment_receipts/%s.pdf',
                    Uuid::uuid4()->toString()
                );
                $payment->setPdfPath($fileName);
            }

            $this->filesystem->dumpFile($this->rootDir . $fileName, $pdf);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            $this->handlePaymentReceiptTemplateException($paymentReceiptTemplate, $exception);
        }
    }

    public function deletePaymentReceiptPdf(Payment $payment): void
    {
        $path = $payment->getPdfPath();
        if (! $path) {
            return;
        }

        try {
            $this->filesystem->remove($this->rootDir . $path);
        } catch (IOException $exception) {
            // silently ignore
        }
        $payment->setPdfPath(null);
    }

    /**
     * Returns full path to payment receipt PDF and regenerates the file if it should exist, but does not.
     */
    public function getFullPaymentReceiptPdfPath(Payment $payment): ?string
    {
        // this is to handle migration from old data
        if ($payment->getClient() && ! $payment->getPdfPath()) {
            $this->savePaymentReceiptPdf($payment);
            $this->entityManager->flush($payment);
        }

        $pdfPath = $payment->getPdfPath();
        if (! $pdfPath) {
            return null;
        }

        $fullPdfPath = $this->rootDir . $pdfPath;

        $pdfPathCheck = Strings::replace($pdfPath, '~^/data/payment_receipts/~', '');
        if (
            (
                Strings::contains($pdfPathCheck, '/')
                || Strings::contains($pdfPathCheck, '\\')
            )
            && $this->filesystem->exists($fullPdfPath)
        ) {
            $this->filesystem->remove($fullPdfPath);
        }

        if (! $this->filesystem->exists($fullPdfPath)) {
            $this->savePaymentReceiptPdf($payment);
            $fullPdfPath = $this->rootDir . $payment->getPdfPath();
        }

        return $fullPdfPath;
    }

    private function handlePaymentReceiptTemplateException(
        PaymentReceiptTemplate $paymentReceiptTemplate,
        \Exception $exception
    ): void {
        if ($paymentReceiptTemplate->isErrorNotificationSent()) {
            throw $exception;
        }

        $this->headerNotifier->sendToAllAdmins(
            HeaderNotification::TYPE_WARNING,
            $this->translator->trans('Payment receipt PDF failed to generate.'),
            strtr(
                $this->translator->trans(
                    'PDF for payment receipt could not be generated due to an error in payment receipt template. Error message: "%errorMessage%"'
                ),
                [
                    '%errorMessage%' => $exception->getMessage(),
                ]
            )
        );
        $paymentReceiptTemplate->setIsValid(false);
        $paymentReceiptTemplate->setErrorNotificationSent(true);
        $this->entityManager->flush($paymentReceiptTemplate);

        throw $exception;
    }
}
