<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Option;

use AppBundle\Entity\Option;
use AppBundle\Event\Tax\TaxAddEvent;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Ds\Queue;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ReplaceSupersededTaxSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Queue|TaxAddEvent[]
     */
    private $taxReplaceEvents;

    public function __construct(Options $options, EntityManager $entityManager)
    {
        $this->options = $options;
        $this->entityManager = $entityManager;
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
        if ($this->taxReplaceEvents->isEmpty()) {
            return;
        }

        foreach ($this->taxReplaceEvents as $event) {
            $this->replaceTax($event);
        }

        $this->options->refresh();
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->taxReplaceEvents->clear();
    }

    private function replaceTax(TaxAddEvent $event): void
    {
        $this->entityManager
            ->getRepository(Option::class)
            ->createQueryBuilder('o')
            ->update()
            ->set('o.value', (string) $event->getTax()->getId())
            ->andWhere('o.code IN (:options)')
            ->andWhere('o.value = :oldTax')
            ->setParameter(
                'options',
                [
                    Option::LATE_FEE_TAX_ID,
                    Option::SETUP_FEE_TAX_ID,
                    Option::EARLY_TERMINATION_FEE_TAX_ID,
                ]
            )
            ->setParameter('oldTax', (string) $event->getSupersededTax()->getId())
            ->getQuery()
            ->execute();
    }
}
