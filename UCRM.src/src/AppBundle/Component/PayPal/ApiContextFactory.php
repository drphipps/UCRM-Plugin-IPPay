<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\PayPal;

use AppBundle\Entity\Organization;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class ApiContextFactory
{
    const MODE_LIVE = 'live';
    const MODE_TEST = 'sandbox';

    public function create(Organization $organization, bool $sandbox): ApiContext
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $organization->getPayPalClientId($sandbox),
                $organization->getPayPalClientSecret($sandbox)
            )
        );
        $apiContext->setConfig(
            [
                'mode' => $sandbox ? self::MODE_TEST : self::MODE_LIVE,
            ]
        );

        return $apiContext;
    }
}
