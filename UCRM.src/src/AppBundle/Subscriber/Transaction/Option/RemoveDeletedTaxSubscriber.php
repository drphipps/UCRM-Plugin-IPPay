<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Option;

use AppBundle\Entity\Option;
use AppBundle\Event\Tax\TaxDeleteEvent;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Ds\Set;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class RemoveDeletedTaxSubscriber implements TransactionEventSubscriberInterface
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
     * @var Set
     */
    private $deletedTaxIds;

    public function __construct(Options $options, EntityManager $entityManager)
    {
        $this->options = $options;
        $this->entityManager = $entityManager;
        $this->deletedTaxIds = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TaxDeleteEvent::class => 'handleTaxDeleteEvent',
        ];
    }

    public function handleTaxDeleteEvent(TaxDeleteEvent $event): void
    {
        $this->deletedTaxIds->add($event->getId());
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
        if ($this->deletedTaxIds->isEmpty()) {
            return;
        }

        $this->entityManager
            ->getRepository(Option::class)
            ->createQueryBuilder('o')
            ->update()
            ->set('o.value', ':value')
            ->andWhere('o.code IN (:options)')
            ->andWhere('o.value IN (:deleted)')
            ->setParameter('value', null)
            ->setParameter(
                'options',
                [
                    Option::LATE_FEE_TAX_ID,
                    Option::SETUP_FEE_TAX_ID,
                    Option::EARLY_TERMINATION_FEE_TAX_ID,
                ]
            )
            ->setParameter('deleted', $this->deletedTaxIds)
            ->getQuery()
            ->execute();

        $this->deletedTaxIds->clear();
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->deletedTaxIds->clear();
    }
}
