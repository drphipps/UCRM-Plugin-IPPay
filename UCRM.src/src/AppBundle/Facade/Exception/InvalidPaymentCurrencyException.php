<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade\Exception;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;

class InvalidPaymentCurrencyException extends \InvalidArgumentException
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var Invoice
     */
    private $invoice;

    public function __construct(Payment $payment, Invoice $invoice)
    {
        parent::__construct(
            sprintf(
                'Payment has currency "%s" but invoice has currency "%s".',
                $payment->getCurrency() ? $payment->getCurrency()->getCode() : null,
                $invoice->getCurrency()->getCode()
            )
        );

        $this->payment = $payment;
        $this->invoice = $invoice;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
