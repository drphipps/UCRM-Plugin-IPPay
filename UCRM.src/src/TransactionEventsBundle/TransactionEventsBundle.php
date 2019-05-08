<?php

declare(strict_types=1);

namespace TransactionEventsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TransactionEventsBundle\DependencyInjection\Compiler\EventGeneratorsPass;
use TransactionEventsBundle\DependencyInjection\ContainerExtension;

class TransactionEventsBundle extends Bundle
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
    public function build(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new EventGeneratorsPass());
    }
}
