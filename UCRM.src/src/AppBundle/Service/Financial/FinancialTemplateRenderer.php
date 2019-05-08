<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Component\AccountStatement\AccountStatement;
use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\FinancialTemplateInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\Option;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Service\CssSanitizer;
use AppBundle\Service\Options;
use AppBundle\Twig\SandboxTemplateRenderer;

class FinancialTemplateRenderer
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var FinancialTemplateParametersProvider
     */
    private $parametersProvider;

    /**
     * @var FinancialTemplateFileManager
     */
    private $financialTemplateFileManager;

    /**
     * @var CssSanitizer
     */
    private $cssSanitizer;

    /**
     * @var DummyFinancialFactory
     */
    private $dummyFinancialFactory;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var SandboxTemplateRenderer
     */
    private $sandboxTemplateRenderer;

    public function __construct(
        \Twig_Environment $twig,
        Options $options,
        FinancialTemplateParametersProvider $parametersProvider,
        FinancialTemplateFileManager $financialTemplateFileManager,
        CssSanitizer $cssSanitizer,
        DummyFinancialFactory $dummyFinancialFactory,
        Pdf $pdf,
        SandboxTemplateRenderer $sandboxTemplateRenderer
    ) {
        $this->twig = $twig;
        $this->options = $options;
        $this->parametersProvider = $parametersProvider;
        $this->financialTemplateFileManager = $financialTemplateFileManager;
        $this->cssSanitizer = $cssSanitizer;
        $this->dummyFinancialFactory = $dummyFinancialFactory;
        $this->pdf = $pdf;
        $this->sandboxTemplateRenderer = $sandboxTemplateRenderer;
    }

    public function renderInvoice(
        Invoice $invoice,
        FinancialTemplateInterface $template,
        bool $includePotentialCredit = false
    ): string {
        return $this->twig->render(
            'client/financial/pdf.html.twig',
            [
                'html' => $this->getInvoiceHtml($invoice, $template, $includePotentialCredit),
                'css' => $this->getSanitizedCss($template),
                'pageSize' => $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER),
            ]
        );
    }

    // returns PDF string with current invoice template, not saved PDF!
    public function renderInvoicePdf(Invoice $invoice): string
    {
        return $this->pdf->generateFromHtml(
            $this->renderInvoice($invoice, $invoice->getTemplate(), $invoice->getInvoiceStatus() === Invoice::DRAFT),
            $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER)
        );
    }

    public function renderAccountStatement(
        AccountStatement $accountStatement,
        AccountStatementTemplate $template
    ): string {
        return $this->twig->render(
            'client/financial/pdf.html.twig',
            [
                'html' => $this->getAccountStatementHtml($accountStatement, $template),
                'css' => $this->getSanitizedCss($template),
                'pageSize' => $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER),
            ]
        );
    }

    public function renderQuote(Quote $quote, QuoteTemplate $template): string
    {
        return $this->twig->render(
            'client/financial/pdf.html.twig',
            [
                'html' => $this->getQuoteHtml($quote, $template),
                'css' => $this->getSanitizedCss($template),
                'pageSize' => $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER),
            ]
        );
    }

    // returns PDF string with current quote template, not saved PDF!
    public function renderQuotePdf(Quote $quote): string
    {
        return $this->pdf->generateFromHtml(
            $this->renderQuote(
                $quote,
                $quote->getQuoteTemplate() ?? $quote->getOrganization()->getQuoteTemplate()
            ),
            $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER)
        );
    }

    private function getInvoiceHtml(
        Invoice $invoice,
        FinancialTemplateInterface $template,
        bool $includePotentialCredit = false
    ): string {
        try {
            $source = $this->financialTemplateFileManager->getSource(
                $template,
                FinancialTemplateFileManager::TWIG_FILENAME
            );

            return $this->sandboxTemplateRenderer->render(
                sprintf('{%% trans_default_domain "invoice_pdf" %%} %s', $source),
                $this->parametersProvider->getInvoiceParameters($invoice, $includePotentialCredit)
            );
        } catch (\Throwable $exception) {
            throw new TemplateRenderException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function getQuoteHtml(Quote $quote, QuoteTemplate $template): string
    {
        try {
            $source = $this->financialTemplateFileManager->getSource(
                $template,
                FinancialTemplateFileManager::TWIG_FILENAME
            );

            return $this->sandboxTemplateRenderer->render(
                sprintf('{%% trans_default_domain "invoice_pdf" %%} %s', $source),
                $this->parametersProvider->getQuoteParameters($quote)
            );
        } catch (\Throwable $exception) {
            throw new TemplateRenderException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function getAccountStatementHtml(
        AccountStatement $accountStatement,
        AccountStatementTemplate $template
    ): string {
        try {
            $source = $this->financialTemplateFileManager->getSource(
                $template,
                FinancialTemplateFileManager::TWIG_FILENAME
            );

            return $this->sandboxTemplateRenderer->render(
                sprintf('{%% trans_default_domain "account_statement_pdf" %%} %s', $source),
                $this->parametersProvider->getAccountStatementParameters($accountStatement)
            );
        } catch (\Throwable $exception) {
            throw new TemplateRenderException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getAccountStatementPdf(
        AccountStatement $accountStatement,
        AccountStatementTemplate $template
    ): string {
        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER);

        return $this->pdf->generateFromHtml(
            $this->renderAccountStatement($accountStatement, $template),
            $pageSize
        );
    }

    public function getPreviewPdf(FinancialTemplateInterface $template): string
    {
        $source = $this->testAgainstDummyData($template);

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_INVOICE, Pdf::PAGE_SIZE_US_LETTER);

        return $this->pdf->generateFromHtml($source, $pageSize);
    }

    public function getPreviewHtml(FinancialTemplateInterface $template): string
    {
        if (
            $template instanceof InvoiceTemplate
            || $template instanceof ProformaInvoiceTemplate
        ) {
            return $this->getInvoiceHtml(
                $this->dummyFinancialFactory->createInvoice(),
                $template
            );
        }
        if ($template instanceof AccountStatementTemplate) {
            return $this->getAccountStatementHtml(
                $this->dummyFinancialFactory->createAccountStatement(),
                $template
            );
        }
        if ($template instanceof QuoteTemplate) {
            return $this->getQuoteHtml(
                $this->dummyFinancialFactory->createQuote(),
                $template
            );
        }
        throw new \InvalidArgumentException('Not supported.');
    }

    public function testAgainstDummyData(FinancialTemplateInterface $template): string
    {
        if ($template instanceof InvoiceTemplate) {
            $this->renderInvoice(
                $this->dummyFinancialFactory->createInvoiceRequiredOnly(),
                $template
            );

            return $this->renderInvoice(
                $this->dummyFinancialFactory->createInvoice(),
                $template
            );
        }
        if ($template instanceof ProformaInvoiceTemplate) {
            $this->renderInvoice(
                $this->dummyFinancialFactory->createProformaInvoiceRequiredOnly(),
                $template
            );

            return $this->renderInvoice(
                $this->dummyFinancialFactory->createProformaInvoice(),
                $template
            );
        }
        if ($template instanceof AccountStatementTemplate) {
            return $this->renderAccountStatement(
                $this->dummyFinancialFactory->createAccountStatement(),
                $template
            );
        }
        if ($template instanceof QuoteTemplate) {
            $this->renderQuote(
                $this->dummyFinancialFactory->createQuoteRequiredOnly(),
                $template
            );

            return $this->renderQuote(
                $this->dummyFinancialFactory->createQuote(),
                $template
            );
        }
        throw new \InvalidArgumentException('Not supported.');
    }

    private function getSanitizedCss(FinancialTemplateInterface $template): string
    {
        $css = $this->financialTemplateFileManager->getSource(
            $template,
            FinancialTemplateFileManager::CSS_FILENAME
        );

        return $this->cssSanitizer->sanitize($css);
    }
}
