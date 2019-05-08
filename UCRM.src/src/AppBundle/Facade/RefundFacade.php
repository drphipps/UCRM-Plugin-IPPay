<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Credit;
use AppBundle\Entity\PaymentCover;
use AppBundle\Entity\Refund;
use AppBundle\Event\Refund\RefundAddEvent;
use AppBundle\Event\Refund\RefundDeleteEvent;
use Doctrine\ORM\EntityManager;
use TransactionEventsBundle\TransactionDispatcher;

class RefundFacade
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(EntityManager $entityManager, TransactionDispatcher $transactionDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleCreate(Refund $refund): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($refund) {
                $this->entityManager->persist($refund);

                yield new RefundAddEvent($refund);
            }
        );
    }

    public function handleDelete(Refund $refund): bool
    {
        if (! $this->setDeleted($refund)) {
            return false;
        }

        $this->transactionDispatcher->transactional(
            function () use ($refund) {
                yield new RefundDeleteEvent($refund);
            }
        );

        return true;
    }

    /**
     * @param int[] $ids
     */
    public function handleDeleteMultipleByIds(array $ids): array
    {
        $refunds = $this->entityManager->getRepository(Refund::class)->findBy(
            [
                'id' => $ids,
            ]
        );

        return $this->handleDeleteMultiple($refunds);
    }

    /**
     * @param Refund[] $refunds
     */
    public function handleDeleteMultiple(array $refunds): array
    {
        $count = count($refunds);
        $deleted = 0;

        $this->transactionDispatcher->transactional(
            function () use ($refunds, &$deleted) {
                foreach ($refunds as $refund) {
                    if (! $this->setDeleted($refund)) {
                        continue;
                    }

                    yield new RefundDeleteEvent($refund);

                    ++$deleted;
                }
            }
        );

        return [$deleted, $count - $deleted];
    }

    private function setDeleted(Refund $refund): bool
    {
        $this->returnCredit($refund);
        $this->entityManager->remove($refund);

        return true;
    }

    private function returnCredit(Refund $refund): void
    {
        $paymentCovers = $this->entityManager->getRepository(PaymentCover::class)->findBy(
            [
                'refund' => $refund,
            ]
        );

        foreach ($paymentCovers as $paymentCover) {
            $credit = $paymentCover->getPayment()->getCredit();
            if ($credit) {
                $credit->setAmount($credit->getAmount() + $paymentCover->getAmount());
            } else {
                $payment = $paymentCover->getPayment();
                $credit = new Credit();
                $credit->setPayment($payment);
                $credit->setClient($refund->getClient());
                $credit->setAmount($paymentCover->getAmount());
                $credit->setType(Credit::OVERPAYMENT);
                $this->entityManager->persist($credit);
                $payment->setCredit($credit);
            }
            $this->entityManager->remove($paymentCover);
        }
    }
}
