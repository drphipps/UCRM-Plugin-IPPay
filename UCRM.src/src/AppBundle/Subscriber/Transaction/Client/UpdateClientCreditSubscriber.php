<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Client;

use AppBundle\Entity\Refund;
use AppBundle\Event\Refund\RefundAddEvent;
use AppBundle\Service\Refund\CreditExhauster;
use Ds\Queue;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdateClientCreditSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|Refund[]
     */
    private $refundsToBeUpdated;

    /**
     * @var CreditExhauster
     */
    private $creditExhauster;

    public function __construct(
        CreditExhauster $creditExhauster
    ) {
        $this->creditExhauster = $creditExhauster;
        $this->refundsToBeUpdated = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RefundAddEvent::class => 'handleRefundAddEvent',
        ];
    }

    public function handleRefundAddEvent(RefundAddEvent $event): void
    {
        $this->refundsToBeUpdated->push($event->getRefund());
    }

    public function preFlush(): void
    {
        foreach ($this->refundsToBeUpdated as $refund) {
            $this->creditExhauster->exhaust($refund);
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->refundsToBeUpdated->clear();
    }
}
