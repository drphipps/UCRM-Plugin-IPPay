<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Invoice\RecurringInvoicesGenerator;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecurringInvoicesCommand extends Command
{
    /**
     * @var RecurringInvoicesGenerator
     */
    private $recurringInvoicesGenerator;

    public function __construct(RecurringInvoicesGenerator $recurringInvoicesGenerator)
    {
        $this->recurringInvoicesGenerator = $recurringInvoicesGenerator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:recurringInvoices:generate')
            ->addArgument(
                'nextInvoicingDay',
                InputArgument::OPTIONAL,
                'Create invoices for this date and before. Format `Y-m-d`.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip check if is correct date and time to creating invoices.'
            )
            ->setDescription('Generate recurring invoices for services.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nextInvoicingDay = $input->getArgument('nextInvoicingDay');
        if (
            null !== $nextInvoicingDay
            && Strings::match($nextInvoicingDay, '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/')
        ) {
            $nextInvoicingDay = new \DateTimeImmutable($nextInvoicingDay);
        } else {
            $nextInvoicingDay = new \DateTimeImmutable();
        }

        $this->recurringInvoicesGenerator->generate(
            $nextInvoicingDay,
            (bool) $input->getOption('force'),
            true
        );

        return 0;
    }
}
