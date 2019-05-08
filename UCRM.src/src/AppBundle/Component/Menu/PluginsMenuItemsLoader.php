<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Exception\PluginManifestException;
use AppBundle\FileManager\PluginDataFileManager;
use AppBundle\Service\Plugin\PluginMenuItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class PluginsMenuItemsLoader
{
    /**
     * @var ApcuAdapter
     */
    private $cache;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PluginDataFileManager
     */
    private $pluginDataFileManager;

    /**
     * @var PluginDataProvider
     */
    private $pluginDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        PluginDataFileManager $pluginDataFileManager,
        PluginDataProvider $pluginDataProvider
    ) {
        $this->cache = new ApcuAdapter('PluginsMenuItemsLoader', 0);
        $this->entityManager = $entityManager;
        $this->pluginDataFileManager = $pluginDataFileManager;
        $this->pluginDataProvider = $pluginDataProvider;
    }

    public function invalidateCache(): void
    {
        $this->cache->deleteItem('menu');
    }

    /**
     * @return PluginMenuItemData[]
     */
    public function load(): array
    {
        $cacheItem = $this->cache->getItem('menu');

        if (! $cacheItem->isHit()) {
            $cacheItem->set($this->loadFromConfiguration());

            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    /**
     * @return PluginMenuItemData[]
     */
    private function loadFromConfiguration(): array
    {
        /** @var Plugin[] $plugins */
        $plugins = $this->entityManager->getRepository(Plugin::class)->findBy(['enabled' => true]);

        $menuItems = [];
        foreach ($plugins as $plugin) {
            try {
                $manifest = $this->pluginDataFileManager->getManifest($plugin);
            } catch (FileNotFoundException | PluginManifestException $exception) {
                // ignore invalid plugins

                continue;
            }

            foreach ($manifest->menu as $item) {
                $data = $this->createMenuItemData($plugin, $item);

                if ($data) {
                    $menuItems[] = $data;
                }
            }
        }

        return $menuItems;
    }

    private function createMenuItemData(Plugin $plugin, PluginMenuItem $item): ?PluginMenuItemData
    {
        $url = $this->pluginDataProvider->getUrl($plugin, $item->parameters);

        if (! $url) {
            return null;
        }

        $data = new PluginMenuItemData();
        $data->pluginId = $plugin->getId();
        $data->pluginName = $plugin->getName();
        $data->key = $item->key;
        $data->label = $item->label ?? $plugin->getDisplayName();
        $data->type = $item->type;
        $data->target = $item->target;
        $data->parameters = $item->parameters;
        $data->link = $url;

        return $data;
    }
}
