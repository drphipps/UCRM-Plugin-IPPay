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
use Symfony\Component\Console\Output\OutputInterface;

class StatisticsDiscardedCommand extends Command
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
        $this->setName('crm:statistics:discard')
            ->setDescription('Mark statistics token as discarded with backup restore.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->statisticsSender->markAsDiscardedWithBackupRestore();

        return 0;
    }
}
