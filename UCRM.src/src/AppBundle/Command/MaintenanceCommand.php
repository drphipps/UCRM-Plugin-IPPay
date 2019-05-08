<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Maintenance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends Command
{
    /**
     * @var Maintenance
     */
    private $maintenance;

    public function __construct(Maintenance $maintenance)
    {
        $this->maintenance = $maintenance;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:maintenance')
            ->setDescription('Deletes old data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->maintenance->run();

        return 0;
    }
}
