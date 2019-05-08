<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

class CreditData
{
    /**
     * @var float
     */
    public $amountPaid;

    /**
     * @var float
     */
    public $amountToPay;

    /**
     * @var float
     */
    public $amountFromCredit;
}
