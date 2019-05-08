<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\FileManager\BackupFileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BackupFacade
{
    /**
     * @var BackupFileManager
     */
    private $backupFileManager;

    public function __construct(BackupFileManager $backupFileManager)
    {
        $this->backupFileManager = $backupFileManager;
    }

    public function handleBackupUploadRestore(UploadedFile $file): void
    {
        $fileName = $this->backupFileManager->handleBackupUpload($file);
        $this->restoreBackup(BackupFileManager::BACKUP_PATH_UPLOADED, $fileName);
    }

    public function deleteUploadedBackup(string $fileName): bool
    {
        return $this->backupFileManager->deleteUploadedBackup($fileName);
    }

    public function requestAutomaticBackup(): void
    {
        $this->backupFileManager->requestAutomaticBackup();
    }

    public function restoreBackup(string $type, string $fileName): void
    {
        $this->backupFileManager->restoreBackup($type, $fileName);
        $this->backupFileManager->triggerMaintenanceMode();
    }
}
