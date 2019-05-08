<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Plugin;

use AppBundle\Entity\Plugin;
use Symfony\Component\EventDispatcher\Event;

class PluginEditEvent extends Event
{
    /**
     * @var Plugin
     */
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}
