<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\AccountStatement\AccountStatement;
use AppBundle\Entity\Client;

class DummyAccountStatementDataProvider extends AbstractAccountStatementDataProvider
{
    public function getAccountStatement(
        Client $client,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        float $initialBalance,
        array $invoices,
        array $payments,
        array $refunds
    ): AccountStatement {
        $accountStatement = new AccountStatement();
        $accountStatement->client = $client;
        $accountStatement->currency = $client->getOrganization()->getCurrency();
        $accountStatement->startDate = $startDate;
        $accountStatement->endDate = $endDate;
        $accountStatement->initialBalance = $initialBalance;
        $accountStatement->items = $this->convertToAccountStatementItems($invoices, $payments, $refunds);

        return $accountStatement;
    }
}
