<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\FileManager\BackupFileManager;

class BackupDataProvider
{
    /**
     * @var string
     */
    private $installedVersion;

    /**
     * @var BackupFileManager
     */
    private $backupFileManager;

    public function __construct(string $installedVersion, BackupFileManager $backupFileManager)
    {
        $this->installedVersion = $installedVersion;
        $this->backupFileManager = $backupFileManager;
    }

    public function getBackupPath(string $type, string $fileName): ?string
    {
        return $this->backupFileManager->getBackupPath($type, $fileName);
    }

    public function getViewData(): array
    {
        $restoreInProgress = $this->backupFileManager->isRestoreInProgress(BackupFileManager::BACKUP_PATH_AUTOMATIC)
            || $this->isUploadedBackupRestoreInProgress();

        return [
            'isBackupRequested' => $this->backupFileManager->isAutomaticBackupRequested(),
            'isRestoreInProgress' => $restoreInProgress,
            'automaticBackupFiles' => $this->getBackupList(BackupFileManager::BACKUP_PATH_AUTOMATIC),
            'uploadedBackupFiles' => $this->getBackupList(BackupFileManager::BACKUP_PATH_UPLOADED),
        ];
    }

    public function isUploadedBackupRestoreInProgress(): bool
    {
        return $this->backupFileManager->isRestoreInProgress(BackupFileManager::BACKUP_PATH_UPLOADED);
    }

    private function getBackupList(string $type): array
    {
        $files = $this->backupFileManager->listBackupDirectory($type);
        foreach ($files as $key => $file) {
            $files[$key]['canRestore'] = version_compare($file['version'], $this->installedVersion, '<=');
        }

        return $files;
    }
}
