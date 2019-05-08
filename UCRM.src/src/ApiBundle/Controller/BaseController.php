<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use AppBundle\Controller\PermissionCheckedInterface;
use AppBundle\Security\PermissionGrantedChecker;
use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class BaseController extends Controller implements PermissionCheckedInterface
{
    use ControllerTrait;

    /**
     * @param object $entity
     *
     * @throws NotFoundHttpException
     */
    protected function notDeleted($entity): void
    {
        if (method_exists($entity, 'isDeleted') && $entity->isDeleted()) {
            throw new NotFoundHttpException(sprintf('%s not found.', get_class($entity)));
        }
    }

    protected function denyAccessUnlessPermissionGranted(string $permissionLevel, string $permissionName): void
    {
        $this->get(PermissionGrantedChecker::class)->denyAccessUnlessGranted($permissionLevel, $permissionName);
    }

    protected function isPermissionGranted(string $permissionLevel, string $permissionName): bool
    {
        return $this->get(PermissionGrantedChecker::class)->isGranted($permissionLevel, $permissionName);
    }

    /**
     * @param mixed|null $data
     */
    protected function routeRedirectViewWithData(
        $data,
        string $route,
        array $parameters = [],
        int $statusCode = Response::HTTP_CREATED,
        array $headers = []
    ): View {
        $view = $this->routeRedirectView($route, $parameters, $statusCode, $headers);
        $view->setData($data);

        return $view;
    }
}
