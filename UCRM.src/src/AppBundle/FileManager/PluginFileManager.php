<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Entity\Plugin;
use AppBundle\Exception\PluginManifestException;
use AppBundle\Exception\PluginUploadException;
use AppBundle\Service\Plugin\PluginManifest;
use AppBundle\Service\Plugin\PluginManifestParser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PluginFileManager extends AbstractPluginFileManager
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $pluginsDir;

    /**
     * @var string
     */
    private $pluginsStagingDir;

    /**
     * @var PluginManifestParser
     */
    private $pluginManifestParser;

    public function __construct(
        string $pluginsDir,
        string $pluginsStagingDir,
        PluginManifestParser $pluginManifestParser
    ) {
        $this->filesystem = new Filesystem();
        $this->pluginsDir = rtrim($pluginsDir, '/');
        $this->pluginsStagingDir = rtrim($pluginsStagingDir, '/');
        $this->pluginManifestParser = $pluginManifestParser;
    }

    public function save(\ZipArchive $zip, PluginManifest $manifest): void
    {
        $path = sprintf('%s/%s', $this->pluginsDir, basename($manifest->information->name));
        // If the plugin directory exists here, it means it's not in database and we can safely override it.
        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }

        $this->filesystem->mkdir($path);
        $zip->extractTo($path);
    }

    public function upgrade(Plugin $plugin, \ZipArchive $zip, PluginManifest $manifest): void
    {
        $dataPath = sprintf('%s/%s/%s', $this->pluginsDir, basename($plugin->getName()), self::DIR_DATA);

        if ($this->filesystem->exists($dataPath)) {
            $stageDataPath = sprintf(
                '%s/%s/%s',
                $this->pluginsStagingDir,
                basename($plugin->getName()),
                self::DIR_DATA
            );

            // clean up old data from staging area
            if ($this->filesystem->exists($stageDataPath)) {
                $this->filesystem->remove($stageDataPath);
            }

            // backup current data to staging area
            $this->filesystem->rename($dataPath, $stageDataPath);
        }

        $this->delete($plugin);
        $this->save($zip, $manifest);

        // move the backed up data from staging area to prod if any
        if (isset($stageDataPath) && $this->filesystem->exists($stageDataPath)) {
            // clean up old data from staging area
            $this->filesystem->rename($stageDataPath, $dataPath, true);
        }
    }

    /**
     * @throws PluginManifestException
     */
    public function getManifestFromZip(\ZipArchive $zip): PluginManifest
    {
        return $this->pluginManifestParser->getVerified($zip->getFromName(self::FILE_MANIFEST));
    }

    public function delete(Plugin $plugin): void
    {
        $this->filesystem->remove(
            sprintf('%s/%s', $this->pluginsDir, basename($plugin->getName()))
        );
    }

    /**
     * @throws PluginUploadException
     */
    public function getVerifiedZipArchive(string $path): \ZipArchive
    {
        $zip = new \ZipArchive();
        // the check must be "true !==" because the open method returns true or error code.
        if (true !== $zip->open($path)) {
            throw new PluginUploadException('ZIP archive could not be opened.');
        }

        $manifest = $zip->getFromName(self::FILE_MANIFEST);
        if (false === $manifest) {
            throw new PluginUploadException('Plugin manifest could not be found in the ZIP archive.');
        }

        $main = $zip->getFromName(self::FILE_MAIN);
        if (false === $main) {
            throw new PluginUploadException('Plugin main file could not be found in the ZIP archive.');
        }

        foreach (self::RESERVED_FILES as $reservedFile) {
            if (false !== $zip->getFromName($reservedFile)) {
                throw new PluginUploadException('Plugin archive contains reserved file and cannot be extracted.');
            }
        }

        return $zip;
    }

    public function createUploadedFile(Plugin $plugin, UploadedFile $uploadedFile, string $filename): void
    {
        $uploadedFile->move(
            sprintf(
                '%s/%s/%s/%s',
                $this->pluginsDir,
                basename($plugin->getName()),
                self::DIR_DATA,
                self::DIR_DATA_FILES
            ),
            basename($filename)
        );
    }

    public function removeUploadedFile(Plugin $plugin, string $filename): void
    {
        $this->filesystem->remove(sprintf('%s/%s', $this->getDataFilesDirectory($plugin), basename($filename)));
    }

    public function getDataFilesDirectory(Plugin $plugin): string
    {
        return sprintf(
            '%s/%s/%s/%s',
            $this->pluginsDir,
            basename($plugin->getName()),
            self::DIR_DATA,
            self::DIR_DATA_FILES
        );
    }
}
