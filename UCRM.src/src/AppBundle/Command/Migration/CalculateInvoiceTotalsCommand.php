<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Migration;

use AppBundle\Service\Financial\InvoiceTotalsCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Introduced in version 2.4.0 to persist totals for all invoices.
 *
 * @todo Can be safely deleted in the future when everyone is on 2.4.0.
 */
class CalculateInvoiceTotalsCommand extends Command
{
    /**
     * @var InvoiceTotalsCalculator
     */
    private $invoiceTotalsCalculator;

    public function __construct(InvoiceTotalsCalculator $invoiceTotalsCalculator)
    {
        $this->invoiceTotalsCalculator = $invoiceTotalsCalculator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:migration:calculateInvoiceTotals');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->invoiceTotalsCalculator->calculate();

        return 0;
    }
}
