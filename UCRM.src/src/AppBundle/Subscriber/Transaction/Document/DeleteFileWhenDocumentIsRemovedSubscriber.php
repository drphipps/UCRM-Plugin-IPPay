<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Document;

use AppBundle\Entity\Document;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Document\DocumentDeleteEvent;
use Ds\Queue;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DeleteFileWhenDocumentIsRemovedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Queue|Document[]
     */
    private $documents;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->documents = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentDeleteEvent::class => 'handleDocumentDeleteEvent',
            ClientDeleteEvent::class => 'handleClientDeleteEvent',
        ];
    }

    public function handleDocumentDeleteEvent(DocumentDeleteEvent $event): void
    {
        $this->documents->push($event->getDocument());
    }

    public function handleClientDeleteEvent(ClientDeleteEvent $event): void
    {
        foreach ($event->getClient()->getDocuments() as $document) {
            $this->documents->push($document);
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
        $filesystem = new Filesystem();

        foreach ($this->documents as $document) {
            $path = $document->getPath();

            try {
                $filesystem->remove($this->rootDir . $path);
            } catch (IOException $e) {
                // Silently ignore.
            }
        }
    }

    public function rollback(): void
    {
        $this->documents->clear();
    }
}
