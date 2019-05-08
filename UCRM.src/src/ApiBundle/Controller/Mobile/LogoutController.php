<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Facade\AuthenticationFacade;
use ApiBundle\Security\ApiAuthenticator;
use ApiBundle\Security\ApiUserProvider;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @Rest\Prefix("/mobile/logout")
 * @Rest\NamePrefix("api_mobile_")
 */
class LogoutController extends BaseController
{
    /**
     * @var AuthenticationFacade
     */
    private $authenticationFacade;

    public function __construct(AuthenticationFacade $authenticationFacade)
    {
        $this->authenticationFacade = $authenticationFacade;
    }

    /**
     * @Rest\Delete("", name="logout")
     * @Rest\View()
     * @Permission("guest")
     */
    public function logoutAction(Request $request): View
    {
        $key = $request->headers->get(ApiAuthenticator::AUTH_HEADER);

        if (! Strings::startsWith($key, ApiUserProvider::USER_KEY_PREFIX)) {
            throw new HttpException(400, 'Logout only works with user keys.');
        }

        $this->authenticationFacade->removeKey(
            Strings::substring($key, Strings::length(ApiUserProvider::USER_KEY_PREFIX))
        );

        return $this->view(
            [
                'code' => 200,
                'message' => 'Authentication key was removed.',
            ]
        );
    }
}
