<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Plugin;

use AppBundle\DataProvider\View\PluginView;
use AppBundle\Entity\Plugin;

class PluginViewFactory
{
    /**
     * @var string
     */
    private $ucrmVersion;

    public function __construct(string $ucrmVersion)
    {
        $this->ucrmVersion = $ucrmVersion;
    }

    public function createFromArray(array $plugin): PluginView
    {
        $view = new PluginView();

        $view->installed = false;
        $view->enabled = false;
        $view->name = $plugin['name'];
        $view->displayName = $plugin['displayName'];
        $view->description = $plugin['description'];
        $view->author = $plugin['author'];
        $view->version = $plugin['version'];
        $view->url = $plugin['url'];
        $view->zipUrl = $plugin['zipUrl'];
        $view->isUcrmVersionCompliant = $this->isUcrmVersionCompliant(
            $plugin['ucrmVersionCompliancy']['min'],
            $plugin['ucrmVersionCompliancy']['max']
        );

        return $view;
    }

    public function createFromEntity(Plugin $plugin): PluginView
    {
        $view = new PluginView();

        $view->installed = true;
        $view->enabled = $plugin->isEnabled();
        $view->id = $plugin->getId();
        $view->name = $plugin->getName();
        $view->displayName = $plugin->getDisplayName();
        $view->description = $plugin->getDescription();
        $view->author = $plugin->getAuthor();
        $view->version = $plugin->getVersion();
        $view->url = $plugin->getUrl();

        return $view;
    }

    private function isUcrmVersionCompliant(string $ucrmVersionCompliancyMin, ?string $ucrmVersionCompliancyMax): bool
    {
        $isCompliant = version_compare(
            $this->ucrmVersion,
            $ucrmVersionCompliancyMin,
            '>='
        );

        if (null !== $ucrmVersionCompliancyMax) {
            $isCompliant = $isCompliant
                && version_compare(
                    $this->ucrmVersion,
                    $ucrmVersionCompliancyMax,
                    '<='
                );
        }

        return $isCompliant;
    }
}
