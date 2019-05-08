<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Invoice\NearDueNotifier;
use AppBundle\Component\Command\Invoice\OverdueNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InvoiceNotificationCommand extends Command
{
    /**
     * @var OverdueNotifier
     */
    private $overdueNotifier;

    /**
     * @var NearDueNotifier
     */
    private $nearDueNotifier;

    public function __construct(OverdueNotifier $overdueNotifier, NearDueNotifier $nearDueNotifier)
    {
        $this->overdueNotifier = $overdueNotifier;
        $this->nearDueNotifier = $nearDueNotifier;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:invoices:sendNotifications')
            ->setDescription('Send invoice notification.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->overdueNotifier->send();
        $this->nearDueNotifier->send();

        return 0;
    }
}
