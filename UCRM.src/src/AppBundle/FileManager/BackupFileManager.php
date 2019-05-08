<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Exception\BackupRestoreException;
use AppBundle\Util\Strings;
use Nette\Utils\Strings as NetteStrings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BackupFileManager
{
    // The regex must be slash (/) limited to enable usage in Finder.
    public const BACKUP_DATABASE_REGEX = '/^.*\.([\d]{10})\.([\d]{1,})\.([\d]{1,})\.([\d]{1,})([-a-z0-9]+)?.*\.tar\.gz$/';

    public const BACKUP_PATH_UPLOADED = 'database-uploaded';
    public const BACKUP_PATH_AUTOMATIC = 'database';

    private const RESTORE_BACKUP_NAME = 'restore_backup.tar.gz';
    private const DATABASE_BACKUP_REQUESTED = '.backup_requested';

    /**
     * @var string
     */
    private $backupPath;

    /**
     * @var MaintenanceFileManager
     */
    private $maintenanceFileManager;

    /**
     * @var string
     */
    private $installedVersion;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $backupPath, string $installedVersion, MaintenanceFileManager $maintenanceFileManager)
    {
        $this->backupPath = $backupPath;
        $this->maintenanceFileManager = $maintenanceFileManager;
        $this->installedVersion = $installedVersion;
        $this->filesystem = new Filesystem();
    }

    public function handleBackupFile(File $file): string
    {
        $fileName = Strings::sanitizeFileName($file->getFilename());

        $file->move(
            $this->getUploadedPath(),
            $fileName
        );

        return $fileName;
    }

    public function handleBackupUpload(UploadedFile $file): string
    {
        $fileName = Strings::sanitizeFileName($file->getClientOriginalName());

        $file->move(
            $this->getUploadedPath(),
            $fileName
        );

        return $fileName;
    }

    public function getBackupPath(string $type, string $fileName): ?string
    {
        $path = sprintf(
            '%s/%s',
            $this->getPathByType($type),
            Strings::sanitizeFileName($fileName)
        );

        return $this->filesystem->exists($path) ? $path : null;
    }

    public function restoreBackup(string $type, string $fileName): void
    {
        $match = NetteStrings::match($fileName, self::BACKUP_DATABASE_REGEX);
        if (! $match) {
            throw new BackupRestoreException(
                'Backup filename is in a wrong format. Example of a valid format: backup_database.1000000000.2.0.0.tar.gz'
            );
        }

        list(, , $major, $minor, $patch, $suffix) = array_pad($match, 6, null);
        $version = sprintf('%d.%d.%d%s', $major, $minor, $patch, $suffix);
        if (! version_compare($version, $this->installedVersion, '<=')) {
            throw new BackupRestoreException(
                'Restore is not possible, you can only restore backup from the same or older version.'
            );
        }

        $this->filesystem->copy(
            $this->getBackupPath($type, $fileName),
            sprintf(
                '%s/%s',
                $this->getPathByType($type),
                self::RESTORE_BACKUP_NAME
            ),
            true
        );
    }

    public function deleteUploadedBackup(string $fileName): bool
    {
        $path = $this->getBackupPath(self::BACKUP_PATH_UPLOADED, $fileName);
        if (! $path) {
            return false;
        }

        $this->filesystem->remove($path);

        return true;
    }

    public function isAutomaticBackupRequested(): bool
    {
        return $this->filesystem->exists(
            sprintf(
                '%s/%s',
                $this->getAutomaticPath(),
                self::DATABASE_BACKUP_REQUESTED
            )
        );
    }

    public function requestAutomaticBackup(): void
    {
        $this->filesystem->touch(
            sprintf(
                '%s/%s',
                $this->getAutomaticPath(),
                self::DATABASE_BACKUP_REQUESTED
            )
        );
    }

    public function isRestoreInProgress(string $type): bool
    {
        return $this->filesystem->exists(
            sprintf(
                '%s/%s',
                $this->getPathByType($type),
                self::RESTORE_BACKUP_NAME
            )
        );
    }

    public function listBackupDirectory(string $type): array
    {
        $path = $this->getPathByType($type);
        $this->filesystem->mkdir($path);
        $finder = new Finder();
        $finder->files()->in($path)->sortByModifiedTime();

        $files = [];
        foreach ($finder as $file) {
            $match = NetteStrings::match($file->getFilename(), self::BACKUP_DATABASE_REGEX);
            if (! $match) {
                continue;
            }

            list(, $timestamp, $major, $minor, $patch, $suffix) = array_pad($match, 6, null);
            $files[] = [
                'fileName' => $file->getFilename(),
                'version' => sprintf('%d.%d.%d%s', $major, $minor, $patch, $suffix),
                'size' => $file->getSize(),
                'createdDate' => $timestamp,
            ];
        }

        return $files;
    }

    public function triggerMaintenanceMode(): void
    {
        $this->maintenanceFileManager->enterMaintenanceMode();
    }

    private function getPathByType(string $type): string
    {
        return $type === self::BACKUP_PATH_AUTOMATIC
            ? $this->getAutomaticPath()
            : $this->getUploadedPath();
    }

    private function getUploadedPath(): string
    {
        return sprintf('%s/%s', $this->backupPath, self::BACKUP_PATH_UPLOADED);
    }

    private function getAutomaticPath(): string
    {
        return sprintf('%s/%s', $this->backupPath, self::BACKUP_PATH_AUTOMATIC);
    }
}
