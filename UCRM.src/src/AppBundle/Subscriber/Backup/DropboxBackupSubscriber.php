<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Backup;

use AppBundle\Event\Backup\BackupDropboxConfigurationChangedEvent;
use AppBundle\Event\Backup\BackupFilesEventInterface;
use AppBundle\Event\Backup\BackupsDeleteEvent;
use AppBundle\Event\Backup\BackupsUploadEvent;
use AppBundle\Service\Backup\BackupEnqueuer;
use AppBundle\Service\BackupCreator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DropboxBackupSubscriber implements EventSubscriberInterface
{
    /**
     * @var BackupEnqueuer
     */
    private $backupEnqueuer;

    /**
     * @var BackupCreator
     */
    private $backupCreator;

    public function __construct(BackupEnqueuer $backupEnqueuer, BackupCreator $backupCreator)
    {
        $this->backupEnqueuer = $backupEnqueuer;
        $this->backupCreator = $backupCreator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BackupDropboxConfigurationChangedEvent::class => 'handleBackupDropboxConfigurationChanged',
            BackupsUploadEvent::class => 'handleEventThroughRabbit',
            BackupsDeleteEvent::class => 'handleEventThroughRabbit',
        ];
    }

    public function handleBackupDropboxConfigurationChanged(): void
    {
        // emits events with specific files
        $this->backupCreator->upload();
    }

    public function handleEventThroughRabbit(BackupFilesEventInterface $backupFilesEvent): void
    {
        $this->backupEnqueuer->enqueue($backupFilesEvent);
    }
}
