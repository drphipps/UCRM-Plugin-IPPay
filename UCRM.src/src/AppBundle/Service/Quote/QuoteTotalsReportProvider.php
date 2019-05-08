<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Quote;

use AppBundle\Entity\Financial\Quote;

class QuoteTotalsReportProvider
{
    /**
     * @param Quote[] $quotes
     */
    public function getTotalsReport(array $quotes): array
    {
        $totalsTemplate = [
            'taxed' => 0,
            'untaxed' => 0,
            'tax' => [],
        ];

        $totals = [];
        $organizations = [];

        foreach ($quotes as $quote) {
            $organizationId = $quote->getOrganization()->getId();
            if (! isset($totals[$organizationId])) {
                $totals[$organizationId] = $totalsTemplate;
                $organizations[$organizationId] = $quote->getOrganization();
            }

            $totals[$organizationId]['taxed'] += $quote->getTotal();
            $totals[$organizationId]['untaxed'] += $quote->getTotalUntaxed();

            foreach ($quote->getTotalTaxes() as $taxName => $taxValue) {
                if (! array_key_exists($taxName, $totals[$organizationId]['tax'])) {
                    $totals[$organizationId]['tax'][$taxName] = 0.0;
                }

                $totals[$organizationId]['tax'][$taxName] += $taxValue;
            }
        }

        return [$totals, $organizations];
    }
}
