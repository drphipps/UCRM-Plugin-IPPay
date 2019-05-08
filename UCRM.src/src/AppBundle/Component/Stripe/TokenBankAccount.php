<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Entity\ClientBankAccount;
use Nette\Utils\Strings;
use Stripe\BankAccount;
use Stripe\Stripe;
use Stripe\Token;

class TokenBankAccount
{
    private const CURRENCY_CODE = 'USD';
    private const COUNTRY_CODE = 'US';

    public function create(ClientBankAccount $bankAccount, bool $isSandbox): Token
    {
        $client = $bankAccount->getClient();
        $organizationCurrency = $client->getOrganization()->getCurrency();
        $organizationCountry = $client->getOrganization()->getCountry();

        if (! $organizationCurrency || $organizationCurrency->getCode() !== self::CURRENCY_CODE) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Wrong organization currency %s. Expected %s.',
                    $organizationCurrency ? $organizationCurrency->getCode() : 'null',
                    self::CURRENCY_CODE
                )
            );
        }
        if ($organizationCountry->getCode() !== self::COUNTRY_CODE) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Wrong organization country %s. Expected %s.',
                    $organizationCountry->getCode(),
                    self::COUNTRY_CODE
                )
            );
        }

        [$routingNumber, $accountNumber] = $this->parseUsBankAccount($bankAccount->getAccountNumber());

        Stripe::setApiKey($client->getOrganization()->getStripeSecretKey($isSandbox));

        return Token::create(
            [
                'bank_account' => [
                    'account_number' => $accountNumber,
                    'account_holder_name' => $client->getNameForView(),
                    'account_holder_type' => $client->isCompany() ? 'company' : 'individual',
                    'country' => self::COUNTRY_CODE,
                    'currency' => strtolower($organizationCurrency->getCode()),
                    'routing_number' => $routingNumber,
                ],
            ]
        );
    }

    public function retrieveClientBankAccount(ClientBankAccount $clientBankAccount, bool $isSandbox): Token
    {
        Stripe::setApiKey($clientBankAccount->getClient()->getOrganization()->getStripeSecretKey($isSandbox));

        return Token::retrieve($clientBankAccount->getStripeBankAccountToken());
    }

    public function verify(
        ClientBankAccount $clientBankAccount,
        bool $isSandbox,
        int $firstDeposit,
        int $secondDeposit
    ): bool {
        Stripe::setApiKey($clientBankAccount->getClient()->getOrganization()->getStripeSecretKey($isSandbox));
        $customer = \Stripe\Customer::retrieve($clientBankAccount->getStripeCustomerId());
        $bankAccount = $customer->sources->retrieve($clientBankAccount->getStripeBankAccountId());
        if ($bankAccount->status === 'verified') {
            return true;
        }
        /** @var BankAccount $bankAccount */
        $bankAccount = $bankAccount->verify(['amounts' => [$firstDeposit, $secondDeposit]]);

        return $bankAccount->status === 'verified';
    }

    private function parseUsBankAccount(string $bankAccount): array
    {
        $matched = Strings::matchAll($bankAccount, '/^(\d{9}):(\d*)$/', PREG_PATTERN_ORDER);
        if (! $matched[0]) {
            throw new \InvalidArgumentException(sprintf('Wrong US bank account number format: %s', $bankAccount));
        }

        return [$matched[1][0], $matched[2][0]];
    }
}
