<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Backup;

use AppBundle\Event\Backup\BackupFilesEventInterface;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\DropboxHandler;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SyncRequestConsumer extends AbstractConsumer
{
    /**
     * @var DropboxHandler
     */
    private $dropboxHandler;

    public function __construct(
        LoggerInterface $logger,
        Options $options,
        EntityManagerInterface $entityManager,
        DropboxHandler $dropboxHandler
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->dropboxHandler = $dropboxHandler;
    }

    protected function getMessageClass(): string
    {
        return RequestBackupMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['operation']) {
            case BackupFilesEventInterface::OPERATION_ADD:
                $this->dropboxHandler->uploadMultiple($data['files']);
                break;
            case BackupFilesEventInterface::OPERATION_DELETE:
                $this->dropboxHandler->delete($data['files']);
                break;
            default:
                $this->logger->error('Unknown operation: ' . $data['operation']);

                return self::MSG_REJECT;
        }

        return self::MSG_ACK;
    }
}
