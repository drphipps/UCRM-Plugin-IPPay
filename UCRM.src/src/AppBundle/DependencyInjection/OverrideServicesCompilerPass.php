<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\DependencyInjection;

use AppBundle\Component\Elastic\Client;
use AppBundle\EventListener\SwitchUserListener;
use AppBundle\Security\AccessMap;
use AppBundle\Translation\Loader\YamlFileLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('security.access_map');
        $definition->setAutowired(true);
        $definition->setClass(AccessMap::class);

        $definition = $container->getDefinition('security.authentication.switchuser_listener');
        $definition->setClass(SwitchUserListener::class);

        $definition = $container->getDefinition('fos_elastica.client_prototype');
        $definition->setAutowired(true);
        $definition->setClass(Client::class);

        $definition = $container->getDefinition('translation.loader.yml');
        $definition->setClass(YamlFileLoader::class);
        $definition->setAutowired(true);

        // setting logger in elastic.yml does not work for object persisters
        $definition = $container->getDefinition('fos_elastica.object_persister');
        $definition->addMethodCall('setLogger', [new Reference(LoggerInterface::class)]);
    }
}
