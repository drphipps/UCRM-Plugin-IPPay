<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace Tests\Listener;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use Symfony\Component\Process\Process;

class ModelSuiteListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() !== 'model') {
            return;
        }

        (new Process('app/console crm:development:doctrine:purge --env=test'))->mustRun();
        (new Process('app/console doctrine:fixtures:load --append --env=test --fixtures=src/AppBundle/Fixtures'))->mustRun();
    }
}
