<?php

declare(strict_types=1);

namespace TransactionEventsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TransactionEventsBundle\DependencyInjection\ContainerExtension;
use TransactionEventsBundle\TransactionDispatcher;

class EventGeneratorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(TransactionDispatcher::class);

        $definition->addArgument(
            array_keys($container->findTaggedServiceIds(ContainerExtension::TAG_TRANSACTION_EVENT_SUBSCRIBER))
        );
    }
}
