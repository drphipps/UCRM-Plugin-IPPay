<?php
/*
 * @copyright Copyright (c) 2019 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Command;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TicketingBundle\Handler\TicketImapImportHandler;

class TicketImapImportCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TicketImapImportHandler
     */
    private $ticketImapImportHandler;

    public function __construct(LoggerInterface $logger, TicketImapImportHandler $ticketImapImportHandler)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->ticketImapImportHandler = $ticketImapImportHandler;
    }

    protected function configure(): void
    {
        $this->setName('crm:ticketing:imap:import')
            ->setDescription('Import new tickets from IMAP server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Import started');

        try {
            $this->ticketImapImportHandler->import();
            $this->logger->info('Import correctly ended');

            return 0;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());

            return 1;
        }
    }
}
