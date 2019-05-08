<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\Quote;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Component\HeaderNotification\HeaderNotifier;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Event\Quote\QuoteEditEvent;
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

    public function saveQuotePdf(Quote $quote): void
    {
        $quoteTemplate = $quote->getQuoteTemplate();
        try {
            $pdf = $this->pdf->generateFromHtml(
                $this->financialTemplateRenderer->renderQuote($quote, $quoteTemplate),
                $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER)
            );
            $fileName = sprintf(
                '/data/quotes/%d/%s.pdf',
                $quote->getOrganization()->getId(),
                Strings::sanitizeFileName($quote->getQuoteNumber())
            );
            $quote->setPdfPath($fileName);

            $this->filesystem->dumpFile($this->rootDir . $fileName, $pdf);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            $this->handleQuoteTemplateException($quoteTemplate, $exception);
        }
    }

    /**
     * Returns full path to quote PDF and regenerates the file if it should exist, but does not.
     */
    public function getFullQuotePdfPath(Quote $quote, bool $forceRegenerate = false): ?string
    {
        $pdfPath = $quote->getPdfPath();
        if (! $pdfPath) {
            return null;
        }

        $quoteBeforeUpdate = clone $quote;

        $fullPdfPath = $this->rootDir . $pdfPath;
        // first check for path with organization ID, then without it to provide backward compatibility
        $pdfPathCheck = NStrings::replace(
            $pdfPath,
            sprintf('~^/data/quotes/%d/~', $quote->getOrganization()->getId()),
            ''
        );
        $pdfPathCheck = NStrings::replace($pdfPathCheck, '~^/data/quotes/~', '');
        if (
            (
                NStrings::contains($pdfPathCheck, '/')
                || NStrings::contains($pdfPathCheck, '\\')
            )
            && $this->filesystem->exists($fullPdfPath)
        ) {
            $this->filesystem->remove($fullPdfPath);
        }

        if ($forceRegenerate || ! $this->filesystem->exists($fullPdfPath)) {
            $this->saveQuotePdf($quote);
            $previousFullPdfPath = $fullPdfPath;
            $fullPdfPath = $this->rootDir . $quote->getPdfPath();

            if ($forceRegenerate) {
                if ($fullPdfPath !== $previousFullPdfPath) {
                    $this->filesystem->remove($previousFullPdfPath);
                }

                $this->transactionDispatcher->transactional(
                    function () use ($quote, $quoteBeforeUpdate) {
                        yield new QuoteEditEvent($quote, $quoteBeforeUpdate);
                    }
                );
            }
        }

        return $fullPdfPath;
    }

    private function handleQuoteTemplateException(QuoteTemplate $quoteTemplate, \Exception $exception): void
    {
        if ($quoteTemplate->isErrorNotificationSent()) {
            throw $exception;
        }

        $this->headerNotifier->sendToAllAdmins(
            HeaderNotification::TYPE_DANGER,
            $this->translator->trans('Quote PDF failed to generate.'),
            strtr(
                $this->translator->trans(
                    'PDF for quote could not be generated due to an error in quote template. Error message: "%errorMessage%"'
                ),
                [
                    '%errorMessage%' => $exception->getMessage(),
                ]
            )
        );
        $quoteTemplate->setIsValid(false);
        $quoteTemplate->setErrorNotificationSent(true);
        $this->entityManager->flush($quoteTemplate);

        throw $exception;
    }
}
