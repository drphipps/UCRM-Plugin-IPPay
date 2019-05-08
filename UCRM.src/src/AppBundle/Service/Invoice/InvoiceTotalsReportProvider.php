<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Invoice;

use AppBundle\Entity\Financial\Invoice;

class InvoiceTotalsReportProvider
{
    /**
     * @param Invoice[] $invoices
     */
    public function getTotalsReport(array $invoices): array
    {
        $totalsTemplate = [
            'taxed' => 0,
            'untaxed' => 0,
            'due' => 0,
            'overdue' => 0,
            'tax' => [],
        ];

        $totals = [];
        $organizations = [];

        $now = new \DateTime();
        foreach ($invoices as $invoice) {
            $organizationId = $invoice->getOrganization()->getId();
            if (! isset($totals[$organizationId])) {
                $totals[$organizationId] = $totalsTemplate;
                $organizations[$organizationId] = $invoice->getOrganization();
            }

            $totals[$organizationId]['taxed'] += $invoice->getTotal();
            $totals[$organizationId]['untaxed'] += $invoice->getTotalUntaxed();

            foreach ($invoice->getTotalTaxes() as $taxName => $taxValue) {
                if (! array_key_exists($taxName, $totals[$organizationId]['tax'])) {
                    $totals[$organizationId]['tax'][$taxName] = 0.0;
                }

                $totals[$organizationId]['tax'][$taxName] += $taxValue;
            }

            if (in_array($invoice->getInvoiceStatus(), Invoice::UNPAID_STATUSES)) {
                $totals[$organizationId]['due'] += $invoice->getAmountToPay();
                if ($invoice->getDueDate() < $now) {
                    $totals[$organizationId]['overdue'] += $invoice->getAmountToPay();
                }
            }
        }

        return [$totals, $organizations];
    }
}
