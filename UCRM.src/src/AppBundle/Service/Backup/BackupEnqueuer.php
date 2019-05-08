<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Backup;

use AppBundle\Event\Backup\BackupFilesEventInterface;
use AppBundle\RabbitMq\Backup\RequestBackupMessage;
use RabbitMqBundle\RabbitMqEnqueuer;

class BackupEnqueuer
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(RabbitMqEnqueuer $rabbitMqEnqueuer)
    {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    public function enqueue(BackupFilesEventInterface $backupFilesEvent): void
    {
        $this->rabbitMqEnqueuer->enqueue(new RequestBackupMessage($backupFilesEvent));
    }
}
