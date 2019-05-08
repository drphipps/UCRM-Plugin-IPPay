<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Form\Data\CreditData;

class InvoiceCalculations
{
    private const CREDIT_APPLICABLE_STATES = [
        Invoice::DRAFT,
        Invoice::UNPAID,
    ];

    public function recalculatePayments(Invoice $invoice): bool
    {
        $fractionDigits = $invoice->getCurrency()->getFractionDigits();
        $changed = false;
        $paid = 0.0;

        foreach ($invoice->getPaymentCovers() as $paymentCover) {
            $paid += $paymentCover->getAmount();
        }

        $paid = round($paid, $fractionDigits);
        if (round($invoice->getAmountPaid(), $fractionDigits) !== $paid) {
            $invoice->setAmountPaid($paid);
            $changed = true;
        }

        if (in_array($invoice->getInvoiceStatus(), Invoice::VALID_STATUSES, true)) {
            $amountToPay = round($invoice->getAmountToPay(), $fractionDigits);
            if ($amountToPay === 0.0) {
                $newStatus = Invoice::PAID;
            } elseif ($amountToPay === round($invoice->getTotal(), $fractionDigits)) {
                $newStatus = Invoice::UNPAID;
            } else {
                $newStatus = Invoice::PARTIAL;
            }

            if ($invoice->getInvoiceStatus() !== $newStatus) {
                $invoice->setInvoiceStatus($newStatus);
                $changed = true;
            }
        }

        return $changed;
    }

    public function calculatePotentialCredit(Invoice $invoice): CreditData
    {
        $amountTotal = $invoice->getTotal();
        $amountPaid = $invoice->getAmountPaid();
        $amountToPay = $invoice->getAmountToPay();
        $fractionDigits = $invoice->getCurrency()->getFractionDigits();

        $amountPotentiallyPaidFromCredit = 0.0;
        if (
            $invoice->getClient()
            && round($amountToPay, $fractionDigits) > 0.0
            && in_array($invoice->getInvoiceStatus(), self::CREDIT_APPLICABLE_STATES, true)
        ) {
            // we are only querying potential credit standings - we don't want to *actually use* the credit at this point
            $credit = $invoice->getClient()->getAccountStandingsCredit();
            if (round($credit, $fractionDigits) > 0.0) {
                if (round($credit + $amountPaid, $fractionDigits) > round($amountTotal, $fractionDigits)) {
                    $amountPotentiallyPaidFromCredit = $amountTotal - $amountPaid;
                    $amountPaid = $amountTotal;
                    $amountToPay = 0.0;
                } elseif (! $invoice->isProforma()) { // Apply to regular invoice only. Total of proforma invoice must be greater than regular invoice.
                    $amountPotentiallyPaidFromCredit = $credit;
                    $amountPaid = $credit + $amountPaid;
                    $amountToPay = $amountTotal - $amountPaid;
                }
            }
        }

        $creditData = new CreditData();
        $creditData->amountPaid = $amountPaid;
        $creditData->amountToPay = $amountToPay;
        $creditData->amountFromCredit = $amountPotentiallyPaidFromCredit;

        return $creditData;
    }
}
