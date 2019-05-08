<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Plugin;

use AppBundle\DataProvider\PluginDataProvider;
use AppBundle\Entity\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginListCommand extends Command
{
    /**
     * @var PluginDataProvider
     */
    private $dataProvider;

    public function __construct(PluginDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('crm:plugin:list');
        $this->setDescription('Lists all enabled plugins for given execution period.');
        $this->addOption(
            'execution-period',
            null,
            InputOption::VALUE_REQUIRED,
            'Filter by execution period, check Plugin::EXECUTION_PERIODS const for possible values.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $executionPeriod = (string) $input->getOption('execution-period');
        if (
            $executionPeriod !== Plugin::EXECUTION_PERIOD_MANUALLY_REQUESTED
            && ! in_array($executionPeriod, Plugin::EXECUTION_PERIODS, true)
        ) {
            throw new \InvalidArgumentException('Execution period not supported.');
        }

        $output->write(
            $this->dataProvider->getListForExecution($executionPeriod),
            true,
            OutputInterface::OUTPUT_RAW
        );

        return 0;
    }
}
