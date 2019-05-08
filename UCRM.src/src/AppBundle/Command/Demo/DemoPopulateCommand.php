<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Demo;

use AppBundle\Component\Demo\DemoDataPopulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoPopulateCommand extends Command
{
    /**
     * @var DemoDataPopulator
     */
    private $demoDataPopulator;

    public function __construct(DemoDataPopulator $demoDataPopulator)
    {
        $this->demoDataPopulator = $demoDataPopulator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:demo:populate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->demoDataPopulator->populate();

        return 0;
    }
}
