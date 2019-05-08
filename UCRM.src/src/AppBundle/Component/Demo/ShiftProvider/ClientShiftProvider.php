<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Demo\ShiftProvider;

class ClientShiftProvider
{
    /**
     * @return string[]
     */
    public function get(): array
    {
        return [
            '
              UPDATE
                client
              SET
                registration_date = registration_date + :difference::interval
            ',
        ];
    }
}
