<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\GoogleCalendar;

use AppBundle\Entity\Option;
use AppBundle\Exception\OAuthException;
use AppBundle\Service\Options;
use Nette\Utils\Json;

class ClientFactory
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function create(?string $accessToken): \Google_Client
    {
        $secret = $this->options->get(Option::GOOGLE_OAUTH_SECRET);
        $client = new \Google_Client();

        try {
            $client->setAuthConfig(Json::decode($secret, Json::FORCE_ARRAY));
        } catch (\Exception $exception) {
            throw new OAuthException(
                'Google OAuth secret is invalid or not set up.',
                $exception->getCode(),
                $exception
            );
        }
        $client->setApplicationName('UCRM');
        $client->setScopes(
            [
                \Google_Service_Calendar::CALENDAR,
            ]
        );
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($accessToken) {
            try {
                $client->setAccessToken(Json::decode($accessToken, Json::FORCE_ARRAY));
            } catch (\Exception $exception) {
                throw new OAuthException('User access token is invalid.', $exception->getCode(), $exception);
            }
        }

        return $client;
    }
}
