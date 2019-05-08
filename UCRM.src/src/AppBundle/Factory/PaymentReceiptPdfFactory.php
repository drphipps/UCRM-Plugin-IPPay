<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;

class PaymentReceiptPdfFactory
{
    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PaymentReceiptTemplateRenderer
     */
    private $paymentReceiptTemplateRenderer;

    public function __construct(
        Pdf $pdf,
        Options $options,
        PaymentReceiptTemplateRenderer $paymentReceiptTemplateRenderer
    ) {
        $this->pdf = $pdf;
        $this->options = $options;
        $this->paymentReceiptTemplateRenderer = $paymentReceiptTemplateRenderer;
    }

    public function create(Payment $payment): string
    {
        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_PAYMENT_RECEIPT, Pdf::PAGE_SIZE_US_LETTER);

        return $this->pdf->generateFromHtml(
            $this->paymentReceiptTemplateRenderer->renderPaymentReceipt(
                $payment,
                $payment->getClient()->getOrganization()->getPaymentReceiptTemplate()
            ),
            $pageSize
        );
    }
}
