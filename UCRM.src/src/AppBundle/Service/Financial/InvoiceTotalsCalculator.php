<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Introduced in version 2.4.0 to persist totals for all invoices.
 *
 * @todo Can be safely deleted in the future when everyone is on 2.4.0.
 */
class InvoiceTotalsCalculator
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    public function __construct(
        Options $options,
        OptionsFacade $optionsFacade,
        EntityManagerInterface $entityManager,
        FinancialTotalCalculator $financialTotalCalculator
    ) {
        $this->options = $options;
        $this->optionsFacade = $optionsFacade;
        $this->entityManager = $entityManager;
        $this->financialTotalCalculator = $financialTotalCalculator;
    }

    public function calculate(): void
    {
        $invoiceTotalsMigrationComplete = $this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE);
        if ($invoiceTotalsMigrationComplete) {
            return;
        }

        $i = 0;
        while ($invoices = $this->entityManager->getRepository(Invoice::class)->findBy([], ['id' => 'ASC'], 100, $i * 100)) {
            foreach ($invoices as $invoice) {
                $this->financialTotalCalculator->computeTotal($invoice);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            ++$i;
        }

        $this->optionsFacade->updateGeneral(
            General::INVOICE_TOTALS_MIGRATION_COMPLETE,
            '1'
        );
    }
}
