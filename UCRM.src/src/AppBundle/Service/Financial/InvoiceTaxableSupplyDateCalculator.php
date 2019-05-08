<?php
/**
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Financial\FinancialItemServiceInterface;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Util\DateTimeImmutableFactory;

class InvoiceTaxableSupplyDateCalculator
{
    public function computeTaxableSupplyDate(Invoice $invoice): ?\DateTimeImmutable
    {
        $maxInvoicedTo = null;
        foreach ($invoice->getInvoiceItems() as $item) {
            if (
                $item instanceof FinancialItemServiceInterface
                && $item->getInvoicedTo()
                && $item->getInvoicedTo() > $maxInvoicedTo
            ) {
                $maxInvoicedTo = clone $item->getInvoicedTo();
            }
        }

        $earliestDate = min(
            array_filter(
                [
                    $invoice->getCreatedDate(),
                    $maxInvoicedTo,
                ]
            ) ?: [null]
        );

        return $earliestDate
            ? DateTimeImmutableFactory::createFromInterface($earliestDate)->modify('midnight')
            : null;
    }
}
