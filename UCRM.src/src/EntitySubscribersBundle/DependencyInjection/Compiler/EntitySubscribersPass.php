<?php

namespace EntitySubscribersBundle\DependencyInjection\Compiler;

use EntitySubscribersBundle\DependencyInjection\ContainerExtension;
use EntitySubscribersBundle\Event\RegisterEntitySubscribersSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EntitySubscribersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(RegisterEntitySubscribersSubscriber::class);
        $taggedServices = $container->findTaggedServiceIds(ContainerExtension::TAG_ENTITY_EVENT_SUBSCRIBER);

        $definition->addArgument(array_keys($taggedServices));
    }
}
