<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Report;

use AppBundle\Facade\ReportDataUsageFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDataUsageReportCommand extends Command
{
    /**
     * @var ReportDataUsageFacade
     */
    private $reportDataUsageFacade;

    public function __construct(ReportDataUsageFacade $reportDataUsageFacade)
    {
        $this->reportDataUsageFacade = $reportDataUsageFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:data-usage-report:generate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->reportDataUsageFacade->generateReportData();

        return 0;
    }
}
