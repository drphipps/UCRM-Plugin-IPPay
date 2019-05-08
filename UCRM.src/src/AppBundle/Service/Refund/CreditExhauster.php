<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Refund;

use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentCover;
use AppBundle\Entity\Refund;
use Doctrine\ORM\EntityManager;

class CreditExhauster
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exhaust(Refund $refund): void
    {
        $amountRefunded = 0.0;
        $fractionDigits = $refund->getCurrency()->getFractionDigits();
        $refundAmountTotal = round($refund->getAmount(), $fractionDigits);

        foreach ($refund->getClient()->getCredits() as $credit) {
            if ($amountRefunded === $refundAmountTotal) {
                break;
            }
            if ($credit->getPayment()->getMethod() === Payment::METHOD_COURTESY_CREDIT) {
                continue;
            }

            $refundAmount = round($refundAmountTotal - $amountRefunded, $fractionDigits);
            $creditAmount = round($credit->getAmount(), $fractionDigits);

            $usedCreditAmount = $refundAmount >= $creditAmount
                ? $creditAmount
                : $refundAmount;

            $credit->setAmount(round($creditAmount - $usedCreditAmount, $fractionDigits));
            if (round($credit->getAmount(), $fractionDigits) === 0.0) {
                $credit->getPayment()->setCredit(null);
                $credit->getClient()->removeCredit($credit);
                $this->entityManager->remove($credit);
            } else {
                $this->entityManager->persist($credit);
            }

            $amountRefunded = round($amountRefunded + $usedCreditAmount, $fractionDigits);
            $paymentCover = new PaymentCover();
            $paymentCover->setRefund($refund);
            $paymentCover->setPayment($credit->getPayment());
            $paymentCover->setAmount($usedCreditAmount);
            $refund->addPaymentCover($paymentCover);
            $this->entityManager->persist($paymentCover);
        }

        $this->entityManager->persist($refund);
    }
}
