<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Import;

use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Event\Import\ImportEditEvent;
use AppBundle\RabbitMq\Import\LoadClientImportMessage;
use Ds\Set;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class EnqueueImportPreviewSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var Set
     */
    private $clientImportIds;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->clientImportIds = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ImportEditEvent::class => 'handleImportEditEvent',
        ];
    }

    public function handleImportEditEvent(ImportEditEvent $event): void
    {
        if (
            $event->getImportBeforeUpdate()->getStatus() !== $event->getImport()->getStatus()
            && $event->getImport()->getStatus() === ImportInterface::STATUS_MAPPED
        ) {
            switch (true) {
                case $event->getImport() instanceof ClientImport:
                    $this->clientImportIds->add($event->getImport()->getId());
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf('Preview creation is not implemented for "%s".', get_class($event->getImport()))
                    );
                // @todo PaymentImport
            }
        }
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->clientImportIds as $importId) {
            $this->rabbitMqEnqueuer->enqueue(new LoadClientImportMessage($importId));
        }
        $this->clientImportIds->clear();
    }

    public function rollback(): void
    {
        $this->clientImportIds->clear();
    }
}
