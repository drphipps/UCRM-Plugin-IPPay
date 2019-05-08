<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Backup;

use AppBundle\Event\Backup\BackupFilesEventInterface;
use Nette\Utils\Json;
use RabbitMqBundle\MessageInterface;

class RequestBackupMessage implements MessageInterface
{
    /**
     * @var BackupFilesEventInterface
     */
    private $backupFilesEvent;

    public function __construct(BackupFilesEventInterface $backupFilesEvent)
    {
        $this->backupFilesEvent = $backupFilesEvent;
    }

    public function getProducer(): string
    {
        return 'backup_sync_request';
    }

    public function getBody(): string
    {
        return Json::encode(
            [
                'operation' => $this->backupFilesEvent->getOperation(),
                'files' => $this->backupFilesEvent->getFiles(),
            ]
        );
    }

    public function getBodyProperties(): array
    {
        return [
            'operation',
            'files',
        ];
    }

    public function getRoutingKey(): string
    {
        return 'backup_sync_request';
    }

    public function getProperties(): array
    {
        return [];
    }
}
