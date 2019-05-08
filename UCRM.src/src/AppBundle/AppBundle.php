<?php

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle;

use AppBundle\DependencyInjection\LazyLoadCommandCompilerPass;
use AppBundle\DependencyInjection\OverrideServicesCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServicesCompilerPass());
        $container->addCompilerPass(new LazyLoadCommandCompilerPass(), PassConfig::TYPE_OPTIMIZE);
    }
}
