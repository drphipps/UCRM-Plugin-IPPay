<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Backup;

interface BackupFilesEventInterface
{
    public const OPERATION_ADD = 1;
    public const OPERATION_DELETE = 2;

    public function getFiles(): array;

    public function getOperation(): int;
}
