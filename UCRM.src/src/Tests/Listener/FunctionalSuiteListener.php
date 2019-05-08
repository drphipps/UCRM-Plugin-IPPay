<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace Tests\Listener;

use Nette\Utils\Strings;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use Symfony\Component\Process\Process;

class FunctionalSuiteListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function startTestSuite(TestSuite $suite): void
    {
        if (! Strings::startsWith($suite->getName(), 'functional') && $suite->getName() !== 'use-case') {
            return;
        }

        (new Process('app/console fos:elastica:populate --env=test'))->mustRun();
    }
}
