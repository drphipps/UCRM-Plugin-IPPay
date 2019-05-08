<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Client;

use AppBundle\Component\AccountStatement\AccountStatement;

class ClientAccountStatementCalculator
{
    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(ClientBalanceFormatter $clientBalanceFormatter)
    {
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    public function calculateBalances(AccountStatement $accountStatement): void
    {
        $initialBalance = $accountStatement->initialBalance;
        $balance = $initialBalance;
        foreach ($accountStatement->items as $item) {
            if ($item->income) {
                $balance += $this->clientBalanceFormatter->getFormattedBalanceRaw($item->amount);
            } else {
                $balance -= $this->clientBalanceFormatter->getFormattedBalanceRaw($item->amount);
            }

            $item->balance = $balance;
        }
        $accountStatement->finalBalance = $balance;
    }
}
