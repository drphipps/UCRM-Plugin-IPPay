<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command\Service;

use AppBundle\Facade\ServiceFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeferredChangesCommand extends Command
{
    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    public function __construct(ServiceFacade $serviceFacade)
    {
        $this->serviceFacade = $serviceFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:services:deferredChanges')
            ->setDescription('Applies all deferred changes that are set for today.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->serviceFacade->applyDeferredChanges(new \DateTimeImmutable('midnight'));

        return 0;
    }
}
