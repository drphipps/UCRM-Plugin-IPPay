<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Organization;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use Doctrine\ORM\EntityManagerInterface;

class TaxReportDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FinancialTotalCalculator
     */
    private $financialTotalCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        FinancialTotalCalculator $financialTotalCalculator
    ) {
        $this->entityManager = $entityManager;
        $this->financialTotalCalculator = $financialTotalCalculator;
    }

    public function getTaxReport(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): array {
        $invoices = $this->getInvoicesForTaxReport($from, $to, $organization);

        $report = [];
        foreach ($invoices as $invoice) {
            $totalData = $this->financialTotalCalculator->computeTotal($invoice);
            foreach ($totalData->taxReport as $taxKey => $invoiceTaxReport) {
                if (array_key_exists($taxKey, $report)) {
                    $report[$taxKey]['total'] += $invoiceTaxReport['total'];
                } else {
                    $report[$taxKey] = [
                        'tax' => $invoiceTaxReport['tax'],
                        'taxRate' => $invoiceTaxReport['taxRate'],
                        'currency' => $invoiceTaxReport['currency'],
                        'total' => $invoiceTaxReport['total'],
                    ];
                }
            }
        }

        return $report;
    }

    /**
     * @return Invoice[]
     */
    private function getInvoicesForTaxReport(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        Organization $organization
    ): array {
        $invoiceRepository = $this->entityManager->getRepository(Invoice::class);
        $qb = $invoiceRepository
            ->createInvoiceRevenueReportQueryBuilder(
                $from,
                $to,
                $organization
            )
            ->join('i.invoiceItems', 'ii')
            ->setParameter('statuses', Invoice::VALID_STATUSES);

        $invoices = $qb->getQuery()->getResult();

        $invoiceIds = array_map(
            function (Invoice $invoice) {
                return $invoice->getId();
            },
            $invoices
        );
        $invoiceRepository->loadRelatedEntities('invoiceItems', $invoiceIds);

        return $invoices;
    }
}
