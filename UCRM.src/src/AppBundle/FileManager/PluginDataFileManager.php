<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Entity\Plugin;
use AppBundle\Exception\PluginIntegrityException;
use AppBundle\Exception\PluginManifestException;
use AppBundle\Service\Plugin\PluginManifest;
use AppBundle\Service\Plugin\PluginManifestParser;
use Nette\Utils\Json;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class PluginDataFileManager extends AbstractPluginFileManager
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
    private $publicPluginsDir;

    /**
     * @var PluginManifestParser
     */
    private $pluginManifestParser;

    public function __construct(
        string $pluginsDir,
        string $publicPluginsDir,
        PluginManifestParser $pluginManifestParser
    ) {
        $this->filesystem = new Filesystem();
        $this->pluginsDir = rtrim($pluginsDir, '/');
        $this->publicPluginsDir = rtrim($publicPluginsDir, '/');
        $this->pluginManifestParser = $pluginManifestParser;
    }

    /**
     * @throws PluginIntegrityException
     */
    public function verifyIntegrity(Plugin $plugin): void
    {
        try {
            $path = $this->getPath($plugin);
        } catch (FileNotFoundException $exception) {
            throw new PluginIntegrityException('Plugin integrity check failed. Required files are missing.');
        }

        if (! $this->filesystem->exists(
            [
                sprintf('%s/%s', $path, self::FILE_MAIN),
                sprintf('%s/%s', $path, self::FILE_MANIFEST),
            ]
        )) {
            throw new PluginIntegrityException('Plugin integrity check failed. Required files are missing.');
        }
    }

    /**
     * @throws FileNotFoundException
     * @throws PluginManifestException
     */
    public function getManifest(Plugin $plugin): PluginManifest
    {
        $path = sprintf('%s/%s', $this->getPath($plugin), self::FILE_MANIFEST);
        if (! $this->filesystem->exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        return $this->pluginManifestParser->getVerified(
            file_get_contents($path)
        );
    }

    public function getConfig(Plugin $plugin): ?array
    {
        try {
            $path = sprintf('%s/%s', $this->getPath($plugin), self::FILE_CONFIG);
            if (! $this->filesystem->exists($path)) {
                return null;
            }
        } catch (FileNotFoundException $exception) {
            return null;
        }

        return Json::decode(file_get_contents($path), Json::FORCE_ARRAY);
    }

    /**
     * @throws FileNotFoundException
     */
    public function saveConfig(Plugin $plugin, array $config): void
    {
        $this->filesystem->dumpFile(
            sprintf('%s/%s', $this->getPath($plugin), self::FILE_CONFIG),
            Json::encode($config)
        );
    }

    public function getUcrmConfig(Plugin $plugin): ?array
    {
        try {
            $path = sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_UCRM_CONFIG);
            if (! $this->filesystem->exists($path)) {
                return null;
            }
        } catch (FileNotFoundException $exception) {
            return null;
        }

        return Json::decode(file_get_contents($path), Json::FORCE_ARRAY);
    }

    /**
     * @throws FileNotFoundException
     */
    public function saveUcrmConfig(Plugin $plugin, array $config): void
    {
        $this->filesystem->dumpFile(
            sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_UCRM_CONFIG),
            Json::encode($config)
        );
    }

    public function getLog(Plugin $plugin): ?string
    {
        try {
            $path = sprintf('%s/%s', $this->getPath($plugin), self::FILE_LOG);
            if (! $this->filesystem->exists($path)) {
                return null;
            }
        } catch (FileNotFoundException $exception) {
            return null;
        }

        return file_get_contents($path);
    }

    public function hasPublicSupport(Plugin $plugin): bool
    {
        try {
            return $this->filesystem->exists(
                sprintf('%s/%s', $this->getPath($plugin), self::FILE_PUBLIC)
            );
        } catch (FileNotFoundException $exception) {
            return false;
        }
    }

    public function isRunning(Plugin $plugin): bool
    {
        try {
            return $this->filesystem->exists(
                sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_RUNNING_LOCK)
            );
        } catch (FileNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Returns true if the plugin's lock file is older than 1 hour (max. execution time).
     */
    public function isHung(Plugin $plugin): bool
    {
        try {
            $lockPath = sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_RUNNING_LOCK);
            if (! $this->filesystem->exists($lockPath)) {
                return false;
            }
        } catch (FileNotFoundException $exception) {
            return false;
        }

        $ctime = filectime($lockPath);
        if ($ctime === false) {
            return false;
        }

        return abs(time() - $ctime) > 3600;
    }

    public function isExecutionRequested(Plugin $plugin): bool
    {
        try {
            return $this->filesystem->exists(
                sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_EXECUTION_REQUESTED)
            );
        } catch (FileNotFoundException $exception) {
            return false;
        }
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    public function requestExecution(Plugin $plugin): void
    {
        $this->filesystem->touch(
            sprintf('%s/%s', $this->getPath($plugin), self::FILE_INTERNAL_EXECUTION_REQUESTED)
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function createPublicSymlink(Plugin $plugin): void
    {
        if (! $this->hasPublicSupport($plugin)) {
            return;
        }

        $this->filesystem->symlink(
            sprintf('%s/%s', $this->getPath($plugin), self::FILE_PUBLIC),
            sprintf('%s/%s/%s', $this->publicPluginsDir, $plugin->getName(), self::FILE_PUBLIC)
        );

        $publicDirPath = sprintf('%s/%s', $this->getPath($plugin), self::DIR_PUBLIC);
        if (! $this->filesystem->exists($publicDirPath)) {
            return;
        }

        $this->filesystem->symlink(
            $publicDirPath,
            sprintf('%s/%s/%s', $this->publicPluginsDir, $plugin->getName(), self::DIR_PUBLIC)
        );
    }

    public function deletePublicSymlink(Plugin $plugin): void
    {
        $this->filesystem->remove(
            sprintf('%s/%s', $this->publicPluginsDir, $plugin->getName())
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function getPath(Plugin $plugin): string
    {
        $path = sprintf('%s/%s', $this->pluginsDir, $plugin->getName());
        if (! $this->filesystem->exists($path)) {
            throw new FileNotFoundException();
        }

        return $path;
    }
}
