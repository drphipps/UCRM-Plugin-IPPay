<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\PaymentCover;
use Doctrine\ORM\EntityManager;

class CreditApplier
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function apply(Invoice $invoice): bool
    {
        if (! in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)) {
            return false;
        }

        $fractionDigits = $invoice->getCurrency()->getFractionDigits();

        if (! $this->canApply($invoice, $fractionDigits)) {
            return false;
        }

        $usedCredit = false;
        foreach ($invoice->getClient()->getCredits() as $credit) {
            if ($credit->getPayment()->getClient() !== $invoice->getClient()) {
                continue;
            }

            $invoiceAmountToPay = round($invoice->getAmountToPay(), $fractionDigits);
            if ($invoiceAmountToPay === 0.0) {
                break;
            }

            $payment = $credit->getPayment();
            $creditAmount = round($credit->getAmount(), $fractionDigits);
            if ($creditAmount === 0.0) {
                continue;
            }

            if ($creditAmount >= $invoiceAmountToPay) {
                $amount = $invoiceAmountToPay;

                $credit->setAmount($creditAmount - $invoiceAmountToPay);
            } else {
                $amount = $creditAmount;

                // the credit needs to be removed from all relations, otherwise it will be persisted again
                $credit->getClient()->removeCredit($credit);
                $payment->setCredit(null);
                $this->em->remove($credit);
            }

            $invoice->setAmountPaid($amount + $invoice->getAmountPaid());
            $invoiceAmountToPay = round($invoice->getAmountToPay(), $fractionDigits);

            if ($invoiceAmountToPay === 0.0) {
                $invoice->setInvoiceStatus(Invoice::PAID);
                $invoice->setUncollectible(false);
            } elseif ($invoiceAmountToPay === round($invoice->getTotal(), $fractionDigits)) {
                $invoice->setInvoiceStatus(Invoice::UNPAID);
            } else {
                $invoice->setInvoiceStatus(Invoice::PARTIAL);
            }

            $paymentCover = new PaymentCover();
            $paymentCover->setInvoice($invoice);
            $paymentCover->setPayment($payment);
            $paymentCover->setAmount($amount);

            $payment->addPaymentCover($paymentCover);
            $invoice->addPaymentCover($paymentCover);

            $this->em->persist($paymentCover);
            $usedCredit = true;
        }

        return $usedCredit;
    }

    private function canApply(Invoice $invoice, int $fractionDigits): bool
    {
        return in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES, true)
            && (
                ! $invoice->isProforma()
                || round($invoice->getClient()->getAccountStandingsCredit(), $fractionDigits)
                >= round($invoice->getAmountToPay(), $fractionDigits)
            );
    }
}
