<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Backup;

use Symfony\Component\EventDispatcher\Event;

class BackupsDeleteEvent extends Event implements BackupFilesEventInterface
{
    /**
     * @var string[]
     */
    private $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getOperation(): int
    {
        return self::OPERATION_DELETE;
    }
}
