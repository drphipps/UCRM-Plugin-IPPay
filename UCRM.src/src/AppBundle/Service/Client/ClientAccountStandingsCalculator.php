<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Client;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;

class ClientAccountStandingsCalculator
{
    public function calculate(Client $client): void
    {
        $accountStandingsCredit = 0.0;
        $accountStandingsRefundableCredit = 0.0;
        foreach ($client->getCredits() as $credit) {
            if ($credit->getPayment()->getClient() !== $client) {
                continue;
            }

            $accountStandingsCredit += $credit->getAmount();

            if ($credit->getPayment()->getMethod() !== Payment::METHOD_COURTESY_CREDIT) {
                $accountStandingsRefundableCredit += $credit->getAmount();
            }
        }

        $accountStandingsOutstanding = 0.0;
        foreach ($client->getInvoices() as $invoice) {
            if (! in_array($invoice->getInvoiceStatus(), Invoice::VALID_STATUSES, true)) {
                continue;
            }

            $accountStandingsOutstanding += $invoice->getAmountToPay();
        }

        $client->setAccountStandingsCredit($accountStandingsCredit);
        $client->setAccountStandingsRefundableCredit($accountStandingsRefundableCredit);
        $client->setAccountStandingsOutstanding($accountStandingsOutstanding);
        $client->setBalance($accountStandingsCredit - $accountStandingsOutstanding);
    }
}
