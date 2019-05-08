<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Ping\PingDataAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PingAggregatorCommand extends Command
{
    /**
     * @var PingDataAggregator
     */
    private $pingDataAggregator;

    public function __construct(PingDataAggregator $pingDataAggregator)
    {
        $this->pingDataAggregator = $pingDataAggregator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:ping:aggregate')
            ->setDescription('Aggregates ping data.')
            ->addOption('short', null, InputOption::VALUE_NONE, 'aggregate short term data (default)')
            ->addOption('long', null, InputOption::VALUE_NONE, 'aggregate long term data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('long')) {
            $this->pingDataAggregator->aggregateLongTerm(PingDataAggregator::TYPE_NETWORK);
            $this->pingDataAggregator->aggregateLongTerm(PingDataAggregator::TYPE_SERVICE);
        } else {
            $this->pingDataAggregator->aggregateShortTerm(PingDataAggregator::TYPE_NETWORK);
            $this->pingDataAggregator->aggregateShortTerm(PingDataAggregator::TYPE_SERVICE);
        }

        return 0;
    }
}
