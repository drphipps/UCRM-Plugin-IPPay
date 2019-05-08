<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Payment;

use AppBundle\Component\IpPay\IpPaySubscriptionProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IpPaySubscriptionProcessCommand extends Command
{
    /**
     * @var IpPaySubscriptionProcessor
     */
    private $processor;

    public function __construct(IpPaySubscriptionProcessor $processor)
    {
        $this->processor = $processor;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:payment:ippaySubscriptions')
            ->setDescription('Process IPPay subscription transactions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process();

        return 0;
    }
}
