<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Backup;

use Symfony\Component\EventDispatcher\Event;

/*
 * We already know what to sync, the subscriber will handle these specified files
 */

class BackupsUploadEvent extends Event implements BackupFilesEventInterface
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
        return self::OPERATION_ADD;
    }
}
