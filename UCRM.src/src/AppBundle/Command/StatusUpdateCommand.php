<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\ServiceOutageUpdater;
use AppBundle\Service\ServiceStatusUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusUpdateCommand extends Command
{
    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var ServiceOutageUpdater
     */
    private $serviceOutageUpdater;

    public function __construct(
        ServiceStatusUpdater $serviceStatusUpdater,
        ClientStatusUpdater $clientStatusUpdater,
        ServiceOutageUpdater $serviceOutageUpdater
    ) {
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->serviceOutageUpdater = $serviceOutageUpdater;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:status:update')
            ->setDescription(
                'Update status of all clients and services based on current data. (E.g. suspended services, overdue invoices, etc.)'
            );
    }

    /**
     * This command is only fallback in case this is not updated manually in the code when doing a change.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->serviceStatusUpdater->updateServices();
        $this->clientStatusUpdater->update();
        $this->serviceOutageUpdater->update();

        return 0;
    }
}
