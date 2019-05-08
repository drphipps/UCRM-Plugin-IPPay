<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\DataProvider\View\PluginView;
use AppBundle\Entity\Plugin;
use AppBundle\Factory\Plugin\PluginViewFactory;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\HttpFoundation\Response;

class PluginListDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PluginViewFactory
     */
    private $pluginViewFactory;

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        EntityManagerInterface $entityManager,
        PluginViewFactory $pluginViewFactory,
        Client $client
    ) {
        $this->entityManager = $entityManager;
        $this->pluginViewFactory = $pluginViewFactory;
        $this->client = $client;
    }

    /**
     * @return PluginView[]
     */
    public function getInstalledPlugins(): array
    {
        $result = $this->entityManager->getRepository(Plugin::class)->findBy(
            [],
            [
                'displayName' => 'ASC',
            ]
        );

        return array_map([$this->pluginViewFactory, 'createFromEntity'], $result);
    }

    /**
     * @return PluginView[][]
     */
    public function getAllPlugins(): array
    {
        $availablePlugins = $this->loadAvailablePlugins();
        $installedPlugins = $this->getInstalledPlugins();

        if ($availablePlugins !== null) {
            $installedPlugins = $this->addAvailableUpdates($installedPlugins, $availablePlugins);
            $availablePlugins = $this->removeInstalledPlugins($availablePlugins, $installedPlugins);
        }

        return [$installedPlugins, $availablePlugins];
    }

    /**
     * Returns array of available plugins or null on failure.
     *
     * @return PluginView[]|null
     */
    private function loadAvailablePlugins(): ?array
    {
        try {
            $response = $this->client->get('', ['timeout' => 5]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return null;
            }

            $body = $response->getBody();

            $data = Json::decode((string) $body, Json::FORCE_ARRAY);

            $plugins = array_map([$this->pluginViewFactory, 'createFromArray'], $data['plugins']);
            usort(
                $plugins,
                function (PluginView $a, PluginView $b) {
                    return $a->displayName <=> $b->displayName;
                }
            );

            return $plugins;
        } catch (JsonException | RequestException $exception) {
            return null;
        }
    }

    /**
     * @param PluginView[] $installedPlugins
     * @param PluginView[] $availablePlugins
     *
     * @return PluginView[]
     */
    private function addAvailableUpdates(array $installedPlugins, array $availablePlugins)
    {
        $pluginsMap = [];
        foreach ($availablePlugins as $pluginView) {
            $pluginsMap[$pluginView->name] = $pluginView;
        }

        foreach ($installedPlugins as $pluginView) {
            if (! array_key_exists($pluginView->name, $pluginsMap)) {
                continue;
            }

            $availablePlugin = $pluginsMap[$pluginView->name];

            if (! version_compare($availablePlugin->version, $pluginView->version, '>')) {
                continue;
            }

            $pluginView->availableVersion = $availablePlugin->version;
            $pluginView->zipUrl = $availablePlugin->zipUrl;
            $pluginView->isUcrmVersionCompliant = $availablePlugin->isUcrmVersionCompliant;
        }

        return $installedPlugins;
    }

    /**
     * @param PluginView[] $installedPlugins
     * @param PluginView[] $availablePlugins
     *
     * @return PluginView[]
     */
    private function removeInstalledPlugins(array $availablePlugins, array $installedPlugins)
    {
        $installedPluginsNames = array_map(
            function (PluginView $pluginView): string {
                return $pluginView->name;
            },
            $installedPlugins
        );

        $nonInstalledPlugins = array_filter(
            $availablePlugins,
            function (PluginView $pluginView) use ($installedPluginsNames) {
                return ! in_array($pluginView->name, $installedPluginsNames, true);
            }
        );

        return array_values($nonInstalledPlugins);
    }
}
