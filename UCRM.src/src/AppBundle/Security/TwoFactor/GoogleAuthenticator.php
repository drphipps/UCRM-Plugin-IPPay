<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security\TwoFactor;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Sonata\GoogleAuthenticator\GoogleAuthenticatorInterface;

/**
 * Overrides original GoogleAuthenticator from bundle to provide database-based configuration instead of config.yml.
 * Handles creating 2FA secrets, checking codes and generating content for QR code.
 */
class GoogleAuthenticator extends \Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator
{
    public const GOOGLE_AUTHENTICATOR_SECRET_SESSION_KEY = 'GOOGLE_AUTHENTICATOR/SECRET/';

    public function __construct(GoogleAuthenticatorInterface $authenticator, Options $options)
    {
        parent::__construct(
            $authenticator,
            '',
            $options->get(Option::SITE_NAME) ?: 'UCRM'
        );
    }
}
