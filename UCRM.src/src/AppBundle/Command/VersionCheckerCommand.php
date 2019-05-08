<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Version\Checker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCheckerCommand extends ContainerAwareCommand
{
    /**
     * @var Checker
     */
    private $checker;

    public function __construct(Checker $checker)
    {
        $this->checker = $checker;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:version:check')
            ->setDescription('CRM version check.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checker->check();

        return 0;
    }
}
