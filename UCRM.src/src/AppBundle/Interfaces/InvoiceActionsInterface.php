<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Interfaces;

use AppBundle\RoutesMap\InvoiceRoutesMap;

interface InvoiceActionsInterface
{
    public function getInvoiceRoutesMap(): InvoiceRoutesMap;
}
