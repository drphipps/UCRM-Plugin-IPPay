<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Facade\AuthenticationFacade;
use ApiBundle\Mapper\UserMapper;
use ApiBundle\Security\ApiUserProvider;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @Rest\Prefix("/mobile/login")
 * @Rest\NamePrefix("api_mobile_")
 */
class LoginController extends BaseController
{
    private const WEEK_SECONDS = 60 * 60 * 24 * 7;

    /**
     * @var AuthenticationFacade
     */
    private $authenticationFacade;

    /**
     * @var UserMapper
     */
    private $userMapper;

    public function __construct(AuthenticationFacade $authenticationFacade, UserMapper $userMapper)
    {
        $this->authenticationFacade = $authenticationFacade;
        $this->userMapper = $userMapper;
    }

    /**
     * @Rest\Post("", name="login")
     * @Rest\View()
     * @Permission("public")
     */
    public function loginAction(Request $request): View
    {
        $expiration = $request->request->getInt('expiration');

        if ($expiration > self::WEEK_SECONDS) {
            throw new HttpException(400, 'Expiration can\'t exceed one week.');
        }

        try {
            $deviceName = $request->request->get('deviceName');
            $authenticationKey = $this->authenticationFacade->createKeyForUser(
                $request->request->get('user'),
                $request->request->get('password'),
                $expiration,
                $request->request->getBoolean('sliding'),
                $deviceName ? Strings::truncate($deviceName, 255, '') : null
            );
        } catch (AuthenticationException $exception) {
            throw new HttpException(401, 'User authentication failed.', $exception);
        }

        return $this->view(
            [
                'authenticationKey' => ApiUserProvider::USER_KEY_PREFIX . $authenticationKey->getKey(),
                'user' => $this->userMapper->reflect($authenticationKey->getUser()),
            ]
        );
    }
}
