<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Plugin;

use AppBundle\Entity\Plugin;
use AppBundle\Event\Option\UrlConfigurationChangedEvent;
use AppBundle\Event\Plugin\PluginAddEvent;
use AppBundle\Event\Plugin\PluginEditEvent;
use AppBundle\Facade\PluginUcrmConfigFacade;
use Ds\Set;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class UpdatePluginUcrmConfigSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var PluginUcrmConfigFacade
     */
    private $pluginUcrmConfigFacade;

    /**
     * @var Set|Plugin[]
     */
    private $plugins;

    public function __construct(PluginUcrmConfigFacade $pluginUcrmConfigFacade)
    {
        $this->pluginUcrmConfigFacade = $pluginUcrmConfigFacade;
        $this->plugins = new Set();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginAddEvent::class => 'handlePluginAddEvent',
            PluginEditEvent::class => 'handlePluginEditEvent',
            UrlConfigurationChangedEvent::class => 'handleUrlConfigurationChangedEvent',
        ];
    }

    public function handlePluginAddEvent(PluginAddEvent $event): void
    {
        $this->plugins->add($event->getPlugin());
    }

    public function handlePluginEditEvent(PluginEditEvent $event): void
    {
        $this->plugins->add($event->getPlugin());
    }

    public function handleUrlConfigurationChangedEvent(UrlConfigurationChangedEvent $event): void
    {
        $this->pluginUcrmConfigFacade->regenerateUcrmConfigs();
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        $this->regenerateConfig($this->plugins->toArray());

        $this->plugins->clear();
    }

    public function rollback(): void
    {
        $this->plugins->clear();
    }

    /**
     * @param Plugin[] $plugins
     */
    private function regenerateConfig(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $this->pluginUcrmConfigFacade->regenerateUcrmConfig($plugin);
        }
    }
}
