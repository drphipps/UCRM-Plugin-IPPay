<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Sandbox\SandboxTerminator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FactoryResetCommand extends Command
{
    /**
     * @var SandboxTerminator
     */
    private $sandboxTerminator;

    public function __construct(SandboxTerminator $sandboxTerminator)
    {
        $this->sandboxTerminator = $sandboxTerminator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:factory-reset')
            ->setDescription('Performs factory reset.')
            ->addOption(
                '--json-config',
                null,
                InputOption::VALUE_REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sandboxTerminator->terminateFromConfigFile((string) $input->getOption('json-config'));

        return 0;
    }
}
