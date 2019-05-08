<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

class PluginManifest
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var PluginManifestInformation
     */
    public $information;

    /**
     * @var PluginManifestConfiguration[]
     */
    public $configuration = [];

    /**
     * @var bool
     */
    public $isUcrmVersionCompliant = false;

    /**
     * @var PluginMenuItem[]
     */
    public $menu;
}
