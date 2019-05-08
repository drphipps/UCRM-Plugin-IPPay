<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider\View;

class PluginView
{
    /**
     * @var bool
     */
    public $installed;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int|null
     */
    public $id;

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
    public $author;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string|null
     */
    public $availableVersion;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string|null
     */
    public $zipUrl;

    /**
     * @var bool|null
     */
    public $isUcrmVersionCompliant;
}
