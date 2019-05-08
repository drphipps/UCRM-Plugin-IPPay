<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Facade\ClientFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AverageMonthlyPaymentUpdateCommand extends Command
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(ClientFacade $clientFacade)
    {
        $this->clientFacade = $clientFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:averageMonthlyPayment:update')
            ->setDescription('Update average monthly payment of all clients.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->clientFacade->updateAverageMonthlyPaymentAllClients();

        return 0;
    }
}
