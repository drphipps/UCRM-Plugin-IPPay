<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Plugin;

class PluginMenuItem
{
    /**
     * @var string|null
     */
    public $key;

    /**
     * @var string|null
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
}
