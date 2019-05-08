<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Service\BackupCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupUploadCommand extends Command
{
    /**
     * @var BackupCreator
     */
    private $backupCreator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BackupCreator $backupCreator, LoggerInterface $logger)
    {
        $this->backupCreator = $backupCreator;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:backup:upload');
        $this->setDescription('Uploads the backup files to remote storage.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->backupCreator->upload();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return 0;
    }
}
