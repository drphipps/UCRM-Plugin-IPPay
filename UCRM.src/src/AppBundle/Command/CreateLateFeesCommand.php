<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Invoice\LateFeeCreator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateLateFeesCommand extends Command
{
    /**
     * @var LateFeeCreator
     */
    private $lateFeeCreator;

    public function __construct(LateFeeCreator $lateFeeCreator)
    {
        $this->lateFeeCreator = $lateFeeCreator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('crm:invoices:createLateFees')
            ->setDescription('Create late fees for overdue invoices.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lateFeeCreator->create();

        return 0;
    }
}
