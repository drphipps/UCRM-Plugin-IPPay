<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Component\Stripe\Exception\StripeException;
use AppBundle\Entity\Organization;
use Stripe\Charge;
use Stripe\Stripe;

class ConnectionCheck
{
    public function check(Organization $organization, bool $sandbox): void
    {
        try {
            Stripe::setApiKey($organization->getStripeSecretKey($sandbox));
            Charge::all(
                [
                    'limit' => 1,
                ]
            );
        } catch (\Exception $exception) {
            throw new StripeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
