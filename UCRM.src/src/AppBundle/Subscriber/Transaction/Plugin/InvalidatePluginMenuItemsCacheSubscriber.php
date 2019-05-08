<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Plugin;

use AppBundle\Component\Menu\PluginsMenuItemsLoader;
use AppBundle\Event\Plugin\PluginAddEvent;
use AppBundle\Event\Plugin\PluginDeleteEvent;
use AppBundle\Event\Plugin\PluginEditEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class InvalidatePluginMenuItemsCacheSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var PluginsMenuItemsLoader
     */
    private $pluginsMenuItemsLoader;

    public function __construct(PluginsMenuItemsLoader $pluginsMenuItemsLoader)
    {
        $this->pluginsMenuItemsLoader = $pluginsMenuItemsLoader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginAddEvent::class => 'handleChange',
            PluginEditEvent::class => 'handleChange',
            PluginDeleteEvent::class => 'handleChange',
        ];
    }

    public function handleChange(): void
    {
        $this->pluginsMenuItemsLoader->invalidateCache();
    }

    public function preFlush(): void
    {
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
    }
}
