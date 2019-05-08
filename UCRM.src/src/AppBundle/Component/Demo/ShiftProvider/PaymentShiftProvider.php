<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\ShiftProvider;

class PaymentShiftProvider
{
    /**
     * @return string[]
     */
    public function get(): array
    {
        return [
            '
              UPDATE
                payment
              SET
                created_date = created_date + :difference::interval
            ',
            '
              UPDATE
                refund
              SET
                created_date = created_date + :difference::interval
            ',
        ];
    }
}
