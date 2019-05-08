<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\User;
use AppBundle\Exception\OAuthException;
use AppBundle\Service\GoogleCalendar\ClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;

class GoogleOAuthFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(EntityManagerInterface $em, ClientFactory $clientFactory)
    {
        $this->em = $em;
        $this->clientFactory = $clientFactory;
    }

    public function createAuthUrl(?string $state): string
    {
        $client = $this->clientFactory->create(null);
        if ($state) {
            $client->setState($state);
        }

        return $client->createAuthUrl();
    }

    public function fetchAccessToken(User $user, string $accessCode): void
    {
        $client = $this->clientFactory->create(null);

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($accessCode);
            $client->setAccessToken($accessToken);
        } catch (\Exception $exception) {
            throw new OAuthException(
                'Request for Google access was not successful.',
                $exception->getCode(),
                $exception
            );
        }

        $user->setGoogleOAuthToken(Json::encode($accessToken));
        $this->em->flush();
    }

    public function refreshTokenIfExpired(User $user): void
    {
        try {
            $client = $this->clientFactory->create($user->getGoogleOAuthToken());
            if ($client->isAccessTokenExpired()) {
                $accessToken = Json::encode($client->fetchAccessTokenWithRefreshToken());
            }
        } catch (\Exception $exception) {
            throw new OAuthException(
                'Request for Google access was not successful.',
                $exception->getCode(),
                $exception
            );
        }

        if (isset($accessToken)) {
            $user->setGoogleOAuthToken($accessToken);
            $this->em->flush();
        }
    }

    public function revokeToken(User $user): void
    {
        $accessToken = $user->getGoogleOAuthToken();
        if (! $accessToken) {
            return;
        }

        $user->removeGoogleCalendar();
        $this->em->flush();

        $client = $this->clientFactory->create($accessToken);
        if ($client->isAccessTokenExpired()) {
            $client->setAccessToken($client->fetchAccessTokenWithRefreshToken());
        }

        // If the token is expired, consider it revoked even if the revoke call fails.
        if (! $client->revokeToken()) {
            throw new OAuthException('Request for Google access revoke was not successful.');
        }
    }
}
