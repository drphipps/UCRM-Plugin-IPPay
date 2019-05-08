<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\AccountStatement\AccountStatementItem;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;

abstract class AbstractAccountStatementDataProvider
{
    /**
     * @param Invoice[] $invoices
     * @param Payment[] $payments
     * @param Refund[]  $refunds
     *
     * @return AccountStatementItem[]
     */
    protected function convertToAccountStatementItems(array $invoices, array $payments, array $refunds): array
    {
        $result = [];

        foreach ($invoices as $invoice) {
            /**
             * TL;DR Do not show generated invoices from the proforma invoice in the account statement.
             * ----------------------------
             * Both invoices has same amount to pay so they will be there twice.
             * Proforma is better to show then regular invoice because account statement is not changed after generating
             * regular invoice.
             */
            if ($invoice->getProformaInvoice()) {
                continue;
            }

            $item = new AccountStatementItem();
            $item->invoice = $invoice;
            $item->currency = $invoice->getCurrency();
            $item->amount = $invoice->getTotal();
            $item->createdDate = $invoice->getCreatedDate();
            $item->income = false;

            $result[$invoice->getCreatedDate()->getTimestamp() . '.1' . $invoice->getId()] = $item;
        }

        foreach ($payments as $payment) {
            $item = new AccountStatementItem();
            $item->payment = $payment;
            $item->currency = $payment->getCurrency();
            $item->amount = $payment->getAmount();
            $item->createdDate = $payment->getCreatedDate();
            $item->income = true;

            $result[$payment->getCreatedDate()->getTimestamp() . '.2' . $payment->getId()] = $item;
        }

        foreach ($refunds as $refund) {
            $item = new AccountStatementItem();
            $item->refund = $refund;
            $item->currency = $refund->getCurrency();
            $item->amount = -$refund->getAmount();
            $item->createdDate = $refund->getCreatedDate();
            $item->income = true;

            $result[$refund->getCreatedDate()->getTimestamp() . '.3' . $refund->getId()] = $item;
        }

        ksort($result, SORT_NATURAL);

        return $result;
    }
}
