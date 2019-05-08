<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

class PluginMenuItemData
{
    /**
     * @var int
     */
    public $pluginId;

    /**
     * @var string
     */
    public $pluginName;

    /**
     * @var string|null
     */
    public $key;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $target;

    /**
     * @var string[]|array[]
     */
    public $parameters = [];

    /**
     * @var string
     */
    public $link;
}
