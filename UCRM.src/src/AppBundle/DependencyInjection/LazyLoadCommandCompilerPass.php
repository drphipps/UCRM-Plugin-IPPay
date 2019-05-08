<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DependencyInjection;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyLoadCommandCompilerPass implements CompilerPassInterface
{
    /**
     * This method finds commands from container definition by tag 'console.command' and adds name of this command
     * as its attribute (eg. crm:unms:changes:sync).
     * With this solution is not needed to do this manually in config YAML file.
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('console.command') as $serviceId => $attributes) {
            $definition = $container->getDefinition($serviceId);
            /** @var Command $command */
            $command = (new \ReflectionClass($definition->getClass()))->newInstanceWithoutConstructor();
            if (array_key_exists('command', $attributes[0])) {
                continue;
            }

            $definition->clearTag('console.command');
            $attributes[0]['command'] = $command->getName();
            $definition->addTag('console.command', $attributes[0]);
        }
    }
}
