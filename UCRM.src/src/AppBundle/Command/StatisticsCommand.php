<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Statistics\StatisticsSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatisticsCommand extends Command
{
    /**
     * @var StatisticsSender
     */
    private $statisticsSender;

    public function __construct(StatisticsSender $statisticsSender)
    {
        $this->statisticsSender = $statisticsSender;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:statistics:send')
            ->setDescription('CRM statistics sender.')
            ->addOption(
                'random-wait',
                null,
                InputOption::VALUE_NONE,
                'Wait for a random number of seconds to avoid overloading the statistics server.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statisticsSender = $this->statisticsSender;
        $randomWait = $input->getOption('random-wait');
        if ($randomWait) {
            $statisticsSender->randomWait();
        }

        return (int) ! $statisticsSender->send();
    }
}
