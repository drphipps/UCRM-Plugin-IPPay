<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Plugin;
use AppBundle\Exception\PluginIntegrityException;
use AppBundle\Exception\PluginManifestException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\FileManager\AbstractPluginFileManager;
use AppBundle\FileManager\PluginDataFileManager;
use AppBundle\Service\LocalUrlGenerator;
use AppBundle\Service\PublicUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class PluginDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PluginDataFileManager
     */
    private $pluginDataFileManager;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var LocalUrlGenerator
     */
    private $localUrlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PluginDataFileManager $pluginDataFileManager,
        PublicUrlGenerator $publicUrlGenerator,
        LocalUrlGenerator $localUrlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->pluginDataFileManager = $pluginDataFileManager;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->localUrlGenerator = $localUrlGenerator;
    }

    public function getListForExecution(string $executionPeriod): array
    {
        $conditions = [
            'enabled' => true,
        ];
        if ($executionPeriod !== Plugin::EXECUTION_PERIOD_MANUALLY_REQUESTED) {
            $conditions['executionPeriod'] = $executionPeriod;
        }
        $plugins = $this->entityManager->getRepository(Plugin::class)->findBy($conditions);
        $list = [];

        foreach ($plugins as $plugin) {
            try {
                // Check the plugin for required files and valid manifest before execution.
                $this->pluginDataFileManager->verifyIntegrity($plugin);
                $manifest = $this->pluginDataFileManager->getManifest($plugin);
            } catch (PluginIntegrityException | PluginManifestException $exception) {
                continue;
            }

            if (! $manifest->isUcrmVersionCompliant) {
                continue;
            }

            if (
                $executionPeriod === Plugin::EXECUTION_PERIOD_MANUALLY_REQUESTED
                && ! $this->pluginDataFileManager->isExecutionRequested($plugin)
            ) {
                continue;
            }

            try {
                $list[] = $this->pluginDataFileManager->getPath($plugin);
            } catch (FileNotFoundException $exception) {
                continue;
            }
        }

        return $list;
    }

    public function getLocalUrl(Plugin $plugin): ?string
    {
        $url = $this->getUrl($plugin);

        if (! $url) {
            return null;
        }

        return rtrim(
                $this->localUrlGenerator->generate('homepage'),
                '/'
            ) . $url;
    }

    public function getPublicUrl(Plugin $plugin): ?string
    {
        $url = $this->getUrl($plugin);

        if (! $url) {
            return null;
        }

        try {
            return rtrim(
                    $this->publicUrlGenerator->generate('homepage'),
                    '/'
                ) . $url;
        } catch (PublicUrlGeneratorException $exception) {
            return null;
        }
    }

    public function getUrl(Plugin $plugin, array $parameters = []): ?string
    {
        if (! $plugin->isEnabled() || ! $this->pluginDataFileManager->hasPublicSupport($plugin)) {
            return null;
        }

        $parameters = http_build_query($parameters);

        return sprintf(
            '/_plugins/%s/%s%s',
            $plugin->getName(),
            AbstractPluginFileManager::FILE_PUBLIC,
            $parameters ? '?' . $parameters : ''
        );
    }
}
