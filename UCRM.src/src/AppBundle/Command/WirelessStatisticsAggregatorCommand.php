<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Component\WirelessStatistics\WirelessStatisticsDataAggregator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WirelessStatisticsAggregatorCommand extends ContainerAwareCommand
{
    /**
     * @var WirelessStatisticsDataAggregator
     */
    private $dataAggregator;

    public function __construct(WirelessStatisticsDataAggregator $dataAggregator)
    {
        $this->dataAggregator = $dataAggregator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:wirelessStatistics:aggregate')
            ->setDescription('Aggregate wireless statistics data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dataAggregator->aggregateLongTerm(WirelessStatisticsDataAggregator::TYPE_NETWORK);
        $this->dataAggregator->aggregateLongTerm(WirelessStatisticsDataAggregator::TYPE_SERVICE);

        return 0;
    }
}
