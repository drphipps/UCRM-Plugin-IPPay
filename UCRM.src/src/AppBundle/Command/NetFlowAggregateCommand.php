<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\NetFlow\DataAggregator;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NetFlowAggregateCommand extends Command
{
    /**
     * @var DataAggregator
     */
    private $dataAggregator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    public function __construct(DataAggregator $dataAggregator, Options $options, OptionsFacade $optionsFacade)
    {
        $this->dataAggregator = $dataAggregator;
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:netflow:aggregate')
            ->setDescription('Aggregates NetFlow data.')
            ->addOption('force');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $input->getOption('force')) {
            $lastAggregationTimestamp = (int) $this->options->getGeneral(General::NETFLOW_LAST_AGGREGATION_TIMESTAMP);
            $frequency = $this->options->get(Option::NETFLOW_AGGREGATION_FREQUENCY, 60) * 60; // convert to seconds

            if (time() - $lastAggregationTimestamp - $frequency < 0) {
                return 0;
            }
        }

        $this->dataAggregator->aggregate(DataAggregator::TYPE_SERVICE);
        $this->dataAggregator->aggregate(DataAggregator::TYPE_NETWORK);

        $this->optionsFacade->updateGeneral(General::NETFLOW_LAST_AGGREGATION_TIMESTAMP, (string) time());

        return 0;
    }
}
