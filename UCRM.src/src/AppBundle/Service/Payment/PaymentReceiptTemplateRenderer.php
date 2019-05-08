<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Payment;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\FileManager\PaymentReceiptTemplateFileManager;
use AppBundle\Service\CssSanitizer;
use AppBundle\Service\HtmlSanitizer;
use AppBundle\Service\Options;
use AppBundle\Twig\SandboxTemplateRenderer;

class PaymentReceiptTemplateRenderer
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
     * @var PaymentReceiptTemplateParametersProvider
     */
    private $parametersProvider;

    /**
     * @var PaymentReceiptTemplateFileManager
     */
    private $paymentReceiptTemplateFileManager;

    /**
     * @var CssSanitizer
     */
    private $cssSanitizer;

    /**
     * @var DummyPaymentFactory
     */
    private $dummyPaymentFactory;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var SandboxTemplateRenderer
     */
    private $sandboxTemplateRenderer;

    /**
     * @var HtmlSanitizer
     */
    private $htmlSanitizer;

    public function __construct(
        \Twig_Environment $twig,
        Options $options,
        PaymentReceiptTemplateParametersProvider $parametersProvider,
        PaymentReceiptTemplateFileManager $paymentReceiptTemplateFileManager,
        CssSanitizer $cssSanitizer,
        DummyPaymentFactory $dummyPaymentFactory,
        Pdf $pdf,
        SandboxTemplateRenderer $sandboxTemplateRenderer,
        HtmlSanitizer $htmlSanitizer
    ) {
        $this->twig = $twig;
        $this->options = $options;
        $this->parametersProvider = $parametersProvider;
        $this->paymentReceiptTemplateFileManager = $paymentReceiptTemplateFileManager;
        $this->cssSanitizer = $cssSanitizer;
        $this->dummyPaymentFactory = $dummyPaymentFactory;
        $this->pdf = $pdf;
        $this->sandboxTemplateRenderer = $sandboxTemplateRenderer;
        $this->htmlSanitizer = $htmlSanitizer;
    }

    public function renderPaymentReceipt(
        Payment $payment,
        PaymentReceiptTemplate $template,
        bool $print = false,
        ?string $nonce = null
    ): string {
        $receiptHtml = $this->getPaymentReceiptHtml($payment, $template);
        if ($print) {
            $receiptHtml = $this->htmlSanitizer->sanitize($receiptHtml);
        }

        return $this->twig->render(
            'client/financial/pdf.html.twig',
            [
                'html' => $receiptHtml,
                'css' => $this->getSanitizedCss($template),
                'pageSize' => $this->options->get(Option::PDF_PAGE_SIZE_PAYMENT_RECEIPT, Pdf::PAGE_SIZE_US_LETTER),
                'print' => $print,
                'nonce' => $nonce,
            ]
        );
    }

    /**
     * @throws TemplateRenderException
     */
    public function getPaymentReceiptHtml(Payment $payment, PaymentReceiptTemplate $template, bool $forEmail = false): string
    {
        try {
            $html = $this->sandboxTemplateRenderer->render(
                $this->paymentReceiptTemplateFileManager->getSource(
                    $template,
                    PaymentReceiptTemplateFileManager::TWIG_FILENAME
                ),
                $this->parametersProvider->getParameters($payment, $forEmail)
            );
        } catch (\Throwable $exception) {
            throw new TemplateRenderException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $html;
    }

    public function getPreviewPdf(PaymentReceiptTemplate $template): string
    {
        $source = $this->testAgainstDummyData($template);

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_PAYMENT_RECEIPT, Pdf::PAGE_SIZE_US_LETTER);

        return $this->pdf->generateFromHtml($source, $pageSize);
    }

    public function getPreviewHtml(PaymentReceiptTemplate $template): string
    {
        return $this->getPaymentReceiptHtml(
            $this->dummyPaymentFactory->createPayment(),
            $template
        );
    }

    public function testAgainstDummyData(PaymentReceiptTemplate $template): string
    {
        $this->renderPaymentReceipt(
            $this->dummyPaymentFactory->createPaymentRequiredOnly(),
            $template
        );

        return $this->renderPaymentReceipt(
            $this->dummyPaymentFactory->createPayment(),
            $template
        );
    }

    public function getSanitizedCss(PaymentReceiptTemplate $template): string
    {
        $css = $this->paymentReceiptTemplateFileManager->getSource(
            $template,
            PaymentReceiptTemplateFileManager::CSS_FILENAME
        );

        return $this->cssSanitizer->sanitize($css);
    }
}
