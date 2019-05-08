<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

class PluginManifestInformation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $displayName;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $ucrmVersionCompliancyMin;

    /**
     * @var string|null
     */
    public $ucrmVersionCompliancyMax;

    /**
     * @var string
     */
    public $author;
}
