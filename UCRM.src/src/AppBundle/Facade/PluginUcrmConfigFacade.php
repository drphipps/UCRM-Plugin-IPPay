<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Plugin;
use AppBundle\FileManager\PluginDataFileManager;
use AppBundle\Service\Plugin\PluginUcrmConfigGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class PluginUcrmConfigFacade
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
     * @var PluginUcrmConfigGenerator
     */
    private $pluginUcrmConfigGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PluginDataFileManager $pluginDataFileManager,
        PluginUcrmConfigGenerator $pluginUcrmConfigGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->pluginDataFileManager = $pluginDataFileManager;
        $this->pluginUcrmConfigGenerator = $pluginUcrmConfigGenerator;
    }

    public function regenerateUcrmConfigs(): void
    {
        $plugins = $this->entityManager->getRepository(Plugin::class)->findAll();

        foreach ($plugins as $plugin) {
            $this->regenerateUcrmConfig($plugin);
        }
    }

    public function regenerateUcrmConfig(Plugin $plugin): void
    {
        try {
            $this->pluginDataFileManager->saveUcrmConfig(
                $plugin,
                $this->pluginUcrmConfigGenerator->getUcrmConfig($plugin)
            );
        } catch (FileNotFoundException $exception) {
            // silently ignore
        }
    }
}
