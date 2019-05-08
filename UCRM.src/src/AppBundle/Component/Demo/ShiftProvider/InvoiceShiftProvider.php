<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\ShiftProvider;

class InvoiceShiftProvider
{
    /**
     * @return string[]
     */
    public function get(): array
    {
        return [
            '
              UPDATE
                invoice
              SET
                created_date = created_date + :difference::interval,
                due_date = due_date + :difference::interval
            ',
            '
              UPDATE
                invoice_item_service
              SET
                invoiced_from = invoiced_from + :difference::interval,
                invoiced_to = invoiced_to + :difference::interval,
                discount_from = discount_from + :difference::interval,
                discount_to = discount_to + :difference::interval
            ',
            '
              UPDATE
                invoice_item_service
              SET
                invoiced_to = (date_trunc(\'MONTH\', invoiced_to) + INTERVAL \'1 MONTH - 1 day\')::date,
                discount_to = (date_trunc(\'MONTH\', discount_to) + INTERVAL \'1 MONTH - 1 day\')::date
            ',
        ];
    }
}
