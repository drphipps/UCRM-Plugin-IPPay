<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Entity\ClientBankAccount;
use Stripe\Stripe;

class Customer
{
    public function create(ClientBankAccount $clientBankAccount, bool $isSandbox, string $token): \Stripe\Customer
    {
        $client = $clientBankAccount->getClient();
        Stripe::setApiKey($client->getOrganization()->getStripeSecretKey($isSandbox));

        return \Stripe\Customer::create(
            [
                'description' => $client->getNameForView() . ' (ID:' . $client->getId() . ')',
                'source' => $token,
                'metadata' => [
                    'accountNumber' => $clientBankAccount->getAccountNumber(),
                ],
            ]
        );
    }

    public function retrieve(string $stripeSecretKey, string $stripeCustomerId): \Stripe\Customer
    {
        Stripe::setApiKey($stripeSecretKey);

        return \Stripe\Customer::retrieve($stripeCustomerId);
    }
}
