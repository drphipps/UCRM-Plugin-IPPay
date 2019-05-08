<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Payment;

use AppBundle\Entity\Credit;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCover;
use AppBundle\Facade\Exception\InvalidPaymentCurrencyException;

class PaymentCoversGenerator
{
    /**
     * Creates PaymentCovers for given invoices and Credit in case of overpayment.
     *
     * @param Invoice[] $invoices
     */
    public function processPayment(Payment $payment, array $invoices): array
    {
        if (! $payment->getCurrency()) {
            return [];
        }

        $fractionDigits = $payment->getCurrency()->getFractionDigits();
        $amount = round($payment->getAmount(), $fractionDigits);

        $entities = [];

        foreach ($invoices as $invoice) {
            if ($invoice->getCurrency() !== $payment->getCurrency()) {
                throw new InvalidPaymentCurrencyException($payment, $invoice);
            }

            if ($amount <= 0.0) {
                break;
            }

            $amountToPay = round($invoice->getAmountToPay(), $fractionDigits);
            if ($amountToPay <= 0.0) {
                continue;
            }

            // Proforma invoice can't be partially paid.
            if ($invoice->isProforma() && $amount < $amountToPay) {
                continue;
            }

            $paymentCoverAmount = min($amount, $amountToPay);
            $entities[] = $this->createPaymentCover($invoice, $payment, $paymentCoverAmount);
            $amount = round($amount - $paymentCoverAmount, $fractionDigits);
        }

        if ($amount > 0.0 && $payment->getClient()) {
            $entities[] = $this->createCredit($payment, $amount);
        }

        return $entities;
    }

    private function createCredit(Payment $payment, float $amount): Credit
    {
        // If payment has credit, just change the values.
        // There was a bug when credit was not deleted when it should have been.
        $credit = $payment->getCredit() ?? new Credit();
        $credit->setAmount($amount);
        $credit->setPayment($payment);
        $credit->setClient($payment->getClient());
        $credit->setType(Credit::OVERPAYMENT);
        $payment->setCredit($credit);

        return $credit;
    }

    private function createPaymentCover(Invoice $invoice, Payment $payment, float $amount): PaymentCover
    {
        $paymentCover = new PaymentCover();
        $paymentCover->setAmount($amount);
        $paymentCover->setInvoice($invoice);
        $paymentCover->setPayment($payment);
        $payment->addPaymentCover($paymentCover);
        $invoice->addPaymentCover($paymentCover);

        return $paymentCover;
    }
}
