<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Payment;

use AppBundle\Component\AuthorizeNet\SubscriptionProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthorizeNetSubscriptionProcessCommand extends Command
{
    /**
     * @var SubscriptionProcessor
     */
    private $subscriptionProcessor;

    public function __construct(SubscriptionProcessor $subscriptionProcessor)
    {
        $this->subscriptionProcessor = $subscriptionProcessor;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:payment:anetSubscriptions')
            ->setDescription('Process Authorize.Net subscription transactions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->subscriptionProcessor->process();

        return 0;
    }
}
