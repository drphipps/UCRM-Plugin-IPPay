<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Service\BackupCreator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCreateCommand extends Command
{
    /**
     * @var BackupCreator
     */
    private $backupCreator;

    public function __construct(BackupCreator $backupCreator)
    {
        $this->backupCreator = $backupCreator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:backup:create');
        $this->addOption('backup-directory', 'd', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->backupCreator->create((string) $input->getOption('backup-directory'));

        return 0;
    }
}
