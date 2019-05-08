<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\DataProvider\UserDataProvider;
use AppBundle\Handler\GoogleCalendar\SynchronizationHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleCalendarSyncCommand extends Command
{
    /**
     * @var SynchronizationHandler
     */
    private $synchronizationHandler;

    /**
     * @var UserDataProvider
     */
    private $dataProvider;

    public function __construct(SynchronizationHandler $synchronizationHandler, UserDataProvider $dataProvider)
    {
        $this->synchronizationHandler = $synchronizationHandler;
        $this->dataProvider = $dataProvider;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:google:calendar:sync')
            ->setDescription('Synchronize Google calendars.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->dataProvider->getUsersForGoogleCalendarSynchronization() as $user) {
            $this->synchronizationHandler->synchronizeUser($user);
        }

        return 0;
    }
}
