<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\UserEditMap;
use ApiBundle\Mapper\UserEditMapper;
use ApiBundle\Mapper\UserMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\UserController as AppUserController;
use AppBundle\DataProvider\UserDataProvider;
use AppBundle\Entity\User;
use AppBundle\Facade\UserFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppUserController::class)
 */
class UserController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var UserFacade
     */
    private $facade;

    /**
     * @var UserMapper
     */
    private $mapper;

    /**
     * @var UserDataProvider
     */
    private $dataProvider;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var UserEditMapper
     */
    private $editMapper;

    public function __construct(
        UserFacade $facade,
        UserMapper $mapper,
        UserDataProvider $dataProvider,
        Validator $validator,
        UserEditMapper $editMapper
    ) {
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
        $this->validator = $validator;
        $this->editMapper = $editMapper;
    }

    /**
     * @Get(
     *     "/users/admins/{id}",
     *     name="user_admin_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAdminAction(User $user): View
    {
        $this->notDeleted($user);

        $this->validateAdminRole($user);

        return $this->view(
            $this->mapper->reflect($user)
        );
    }

    /**
     * @Patch(
     *     "/users/admins/{id}"
     * )
     * @ViewHandler()
     * @ParamConverter("userEditMap", converter="fos_rest.request_body")
     * @Permission("edit")
     */
    public function patchAdminAction(User $user, UserEditMap $userEditMap): View
    {
        $this->notDeleted($user);

        $this->validateAdminRole($user);

        $this->editMapper->map($userEditMap, $user);

        $this->validator->validate($user, $this->editMapper->getFieldsDifference());

        $this->facade->handleUpdate($user);

        return $this->view(
            $this->mapper->reflect($user)
        );
    }

    /**
     * @Get("/users/admins", name="user_admin_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAdminCollectionAction(): View
    {
        return $this->view(
            $this->mapper->reflectCollection(
                $this->dataProvider->getAllAdmins()
            )
        );
    }

    private function validateAdminRole(User $user)
    {
        if (! in_array($user->getRole(), User::ADMIN_ROLES, true)) {
            throw new NotFoundHttpException('User not found.');
        }
    }
}
