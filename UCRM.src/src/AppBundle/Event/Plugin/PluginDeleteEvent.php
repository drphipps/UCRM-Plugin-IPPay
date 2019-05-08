<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Plugin;

use AppBundle\Entity\Plugin;
use Symfony\Component\EventDispatcher\Event;

class PluginDeleteEvent extends Event
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var int
     */
    private $id;

    public function __construct(Plugin $plugin, int $id)
    {
        $this->plugin = $plugin;
        $this->id = $id;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
