<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\Util\Helpers;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AccessMap extends \Symfony\Component\Security\Http\AccessMap
{
    /**
     * {@inheritdoc}
     */
    public function add(RequestMatcherInterface $requestMatcher, array $attributes = [], $channel = null): void
    {
        parent::add(
            $requestMatcher,
            $attributes,
            Helpers::forceHttps() ? 'https' : $channel
        );
    }
}
