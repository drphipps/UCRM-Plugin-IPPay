<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Util\Strings;
use Symfony\Component\Filesystem\Filesystem;

class UpdatesFileManager
{
    private const UPDATE_REQUESTED_FILE = 'update_requested';
    private const UPDATE_MAINTENANCE_FILE = 'update_maintenance';
    private const UPDATE_LOG_FILE = 'update.log';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $updateRequestedFilePath;

    /**
     * @var string
     */
    private $updateMaintenanceFilePath;

    /**
     * @var string
     */
    private $updateLogFilePath;

    /**
     * @var MaintenanceFileManager
     */
    private $maintenanceFileManager;

    public function __construct(string $updatesDir, MaintenanceFileManager $maintenanceFileManager)
    {
        $this->maintenanceFileManager = $maintenanceFileManager;

        $this->filesystem = new Filesystem();
        $this->updateRequestedFilePath = $updatesDir . '/' . self::UPDATE_REQUESTED_FILE;
        $this->updateMaintenanceFilePath = $updatesDir . '/' . self::UPDATE_MAINTENANCE_FILE;
        $this->updateLogFilePath = $updatesDir . '/' . self::UPDATE_LOG_FILE;
    }

    public function getRequestedUpdate(): ?string
    {
        return $this->filesystem->exists($this->updateRequestedFilePath)
            ? file_get_contents($this->updateRequestedFilePath)
            : null;
    }

    public function requestUpdate(string $version, string $updateFileAccessKey): void
    {
        $this->filesystem->dumpFile($this->updateRequestedFilePath, $version);
        $this->filesystem->dumpFile($this->updateMaintenanceFilePath, $updateFileAccessKey);
    }

    /**
     * @deprecated only used in tests, when the update is requested UCRM should switch to maintenance mode almost instantly
     */
    public function cancelUpdate(): void
    {
        $this->filesystem->remove($this->updateRequestedFilePath);
        $this->filesystem->remove($this->updateMaintenanceFilePath);
    }

    public function getUpdateLog(): ?string
    {
        return $this->filesystem->exists($this->updateLogFilePath)
            ? Strings::stripAnsi(file_get_contents($this->updateLogFilePath))
            : null;
    }
}
