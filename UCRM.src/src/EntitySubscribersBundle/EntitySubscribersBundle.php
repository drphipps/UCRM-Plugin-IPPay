<?php

namespace EntitySubscribersBundle;

use EntitySubscribersBundle\DependencyInjection\Compiler\EntitySubscribersPass;
use EntitySubscribersBundle\DependencyInjection\ContainerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EntitySubscribersBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ContainerExtension
    {
        return new ContainerExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addCompilerPass(new EntitySubscribersPass());
    }
}
