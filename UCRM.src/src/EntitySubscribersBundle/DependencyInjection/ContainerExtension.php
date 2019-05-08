<?php

namespace EntitySubscribersBundle\DependencyInjection;

use EntitySubscribersBundle\Event\EntityEventSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerExtension extends Extension
{
    public const TAG_ENTITY_EVENT_SUBSCRIBER = 'entity_subscribers.entity_subscriber';

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'entity_listeners';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->registerForAutoconfiguration(EntityEventSubscriber::class)
            ->addTag(self::TAG_ENTITY_EVENT_SUBSCRIBER)
            ->addTag('doctrine.orm.entity_listener');
    }
}
