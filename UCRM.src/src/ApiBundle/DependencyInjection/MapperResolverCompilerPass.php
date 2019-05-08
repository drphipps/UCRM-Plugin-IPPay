<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\DependencyInjection;

use Nette\Utils\Strings;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MapperResolverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $mappers = [];
        foreach ($container->findTaggedServiceIds('entity_mapper') as $serviceId => $tags) {
            /**
             * Tagged services are like \XyzzyBundle\Baz\FooBarMapper;
             *  and we need to find them using MapperResolver->get("foobar")
             *  // @see MapperResolver::get()
             * In the example above, we match on \(FooBar)Mapper and use it as the array key "foobar":.
             */
            $name = Strings::lower(Strings::replace($serviceId, '/.*\\\\([A-Za-z]+)Mapper$/', '$1'));
            $mappers[$name] = $serviceId;
        }

        $resolver = new Definition();
        $resolver->setClass(MapperResolver::class);
        $resolver->setArgument(0, $mappers);
        $resolver->setAutowired(true);

        $container->addDefinitions(['api.mapper_resolver' => $resolver]);
    }
}
