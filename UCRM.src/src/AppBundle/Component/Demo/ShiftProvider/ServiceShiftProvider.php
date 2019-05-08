<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\ShiftProvider;

class ServiceShiftProvider
{
    /**
     * @return string[]
     */
    public function get(): array
    {
        return [
            '
              UPDATE
                service
              SET
                active_from = active_from + :difference::interval,
                active_to = active_to + :difference::interval,
                invoicing_start = invoicing_start + :difference::interval,
                contract_end_date = contract_end_date + :difference::interval,
                next_invoicing_day = next_invoicing_day + :difference::interval,
                prev_invoicing_day = prev_invoicing_day + :difference::interval,
                discount_from = discount_from + :difference::interval,
                discount_to = discount_to + :difference::interval,
                suspended_from = suspended_from + :difference::interval,
                deleted_at = deleted_at + :difference::interval,
                invoicing_last_period_end = invoicing_last_period_end + :difference::interval,
                active_to_backup = active_to_backup + :difference::interval
            ',
            '
              UPDATE
                service
              SET
                invoicing_last_period_end = (date_trunc(\'MONTH\', invoicing_last_period_end) + INTERVAL \'1 MONTH - 1 day\')::date
              WHERE
                invoicing_last_period_end IS NOT NULL
            ',
            '
              UPDATE
                service
              SET
                discount_to = (date_trunc(\'MONTH\', discount_to) + INTERVAL \'1 MONTH - 1 day\')::date
              WHERE
                discount_to IS NOT NULL
            ',
        ];
    }
}
