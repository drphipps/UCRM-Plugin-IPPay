<?php

namespace RabbitMqBundle;

use RabbitMqBundle\DependencyInjection\Compiler\ProducersPass;
use RabbitMqBundle\DependencyInjection\ContainerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RabbitMqBundle extends Bundle
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
        $containerBuilder->addCompilerPass(new ProducersPass());
    }
}
