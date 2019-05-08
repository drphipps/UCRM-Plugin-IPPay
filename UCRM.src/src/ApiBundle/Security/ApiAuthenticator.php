<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiAuthenticator extends AbstractGuardAuthenticator
{
    public const AUTH_HEADER = 'X-Auth-App-Key';

    public function getCredentials(Request $request): array
    {
        if (! $token = $request->headers->get(self::AUTH_HEADER)) {
            $token = null;
        }

        return [
            'token' => $token,
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $apiKey = $credentials['token'];

        if (null === $apiKey) {
            return null;
        }

        return $userProvider->loadUserByUsername($apiKey);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Authentication Required',
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Authentication Required',
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function supports(Request $request)
    {
        return true;
    }
}
