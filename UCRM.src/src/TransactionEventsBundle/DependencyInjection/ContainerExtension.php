<?php

declare(strict_types=1);

namespace TransactionEventsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ContainerExtension extends Extension
{
    public const TAG_TRANSACTION_EVENT_SUBSCRIBER = 'transaction_events.transaction_event_subscriber';

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'transaction_events';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->registerForAutoconfiguration(TransactionEventSubscriberInterface::class)
            ->addTag(self::TAG_TRANSACTION_EVENT_SUBSCRIBER);
    }
}
