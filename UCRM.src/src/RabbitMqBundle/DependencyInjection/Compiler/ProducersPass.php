<?php

namespace RabbitMqBundle\DependencyInjection\Compiler;

use Nette\Utils\Strings;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ProducersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(RabbitMqEnqueuer::class);
        $taggedServices = $container->findTaggedServiceIds('old_sound_rabbit_mq.producer');
        $producers = [];

        foreach (array_keys($taggedServices) as $producer) {
            $match = Strings::match($producer, '~old_sound_rabbit_mq\\.([a-z0-9_]+)_producer~');
            if (! $match) {
                throw new \RuntimeException(
                    sprintf(
                        'Invalid producer name "%s"',
                        $producer
                    )
                );
            }

            $producers[$match[1]] = $producer;
        }

        $definition->addArgument($producers);
    }
}
