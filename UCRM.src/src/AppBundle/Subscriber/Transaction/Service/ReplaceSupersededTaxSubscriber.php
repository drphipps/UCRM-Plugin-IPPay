<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Service;

use AppBundle\Entity\Service;
use AppBundle\Event\Tax\TaxAddEvent;
use AppBundle\Service\Tax\TaxReplacer;
use Ds\Queue;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ReplaceSupersededTaxSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var TaxReplacer
     */
    private $taxReplacer;

    /**
     * @var Queue|TaxAddEvent[]
     */
    private $taxReplaceEvents;

    public function __construct(TaxReplacer $taxReplacer)
    {
        $this->taxReplacer = $taxReplacer;
        $this->taxReplaceEvents = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TaxAddEvent::class => 'handleTaxAddEvent',
        ];
    }

    public function handleTaxAddEvent(TaxAddEvent $event): void
    {
        if ($event->getSupersededTax()) {
            $this->taxReplaceEvents->push($event);
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
        foreach ($this->taxReplaceEvents as $event) {
            $this->taxReplacer->replaceTax(Service::class, 'tax1', $event->getTax(), $event->getSupersededTax());
            $this->taxReplacer->replaceTax(Service::class, 'tax2', $event->getTax(), $event->getSupersededTax());
            $this->taxReplacer->replaceTax(Service::class, 'tax3', $event->getTax(), $event->getSupersededTax());
        }
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->taxReplaceEvents->clear();
    }
}
