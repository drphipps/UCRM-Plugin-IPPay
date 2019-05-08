<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\Entity\Plugin;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Service\LocalUrlGenerator;
use AppBundle\Service\PublicUrlGenerator;

class PluginUcrmConfigGenerator
{
    /**
     * @var LocalUrlGenerator
     */
    private $localUrlGenerator;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var PluginDataProvider
     */
    private $pluginDataProvider;

    public function __construct(
        PublicUrlGenerator $publicUrlGenerator,
        LocalUrlGenerator $localUrlGenerator,
        PluginDataProvider $pluginDataProvider
    ) {
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->localUrlGenerator = $localUrlGenerator;
        $this->pluginDataProvider = $pluginDataProvider;
    }

    public function getUcrmConfig(Plugin $plugin): array
    {
        try {
            $ucrmPublicUrl = $this->publicUrlGenerator->generate('homepage');
        } catch (PublicUrlGeneratorException $exception) {
            $ucrmPublicUrl = null;
        }

        return [
            'ucrmPublicUrl' => $ucrmPublicUrl,
            'ucrmLocalUrl' => $this->localUrlGenerator->generate('homepage'),
            'pluginPublicUrl' => $this->pluginDataProvider->getPublicUrl($plugin),
            'pluginAppKey' => $plugin->getAppKey() ? $plugin->getAppKey()->getKey() : null,
            'pluginId' => $plugin->getId(),
        ];
    }
}
