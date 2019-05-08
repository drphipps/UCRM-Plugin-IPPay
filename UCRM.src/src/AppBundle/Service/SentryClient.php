<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use Sentry\SentryBundle\SentrySymfonyClient;

class SentryClient extends SentrySymfonyClient
{
    /**
     * As we are using custom way of sending errors and exceptions to Sentry, we don't want to use the provided
     * handlers. Also when the provided handlers are used, Symfony's exception controller does not work and only blank screen
     * is displayed.
     */
    public function install()
    {
        // leave this empty
    }
}
