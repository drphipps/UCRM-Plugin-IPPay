<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory\Financial;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\PaymentToken;

class PaymentTokenFactory
{
    public function create(Invoice $invoice): PaymentToken
    {
        $token = new PaymentToken();
        $token->setCreated(new \DateTime());
        $token->setInvoice($invoice);
        $token->generateToken();
        $invoice->setPaymentToken($token);

        return $token;
    }
}
