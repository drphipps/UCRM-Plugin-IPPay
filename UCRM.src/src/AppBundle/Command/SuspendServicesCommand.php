<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Suspension\ServiceActivator;
use AppBundle\Component\Command\Suspension\ServiceSuspender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SuspendServicesCommand extends Command
{
    /**
     * @var ServiceActivator
     */
    private $serviceActivator;

    /**
     * @var ServiceSuspender
     */
    private $serviceSuspender;

    public function __construct(ServiceActivator $serviceActivator, ServiceSuspender $serviceSuspender)
    {
        $this->serviceActivator = $serviceActivator;
        $this->serviceSuspender = $serviceSuspender;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:services:suspend')
            ->setDescription('Suspend and unsuspend services.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->serviceActivator->activate();
        $this->serviceSuspender->suspend();

        return 0;
    }
}
