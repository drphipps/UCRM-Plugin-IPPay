<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Site;
use AppBundle\Facade\SiteFacade;
use AppBundle\Form\SiteType;
use AppBundle\Grid\Device\SiteDeviceGridFactory;
use AppBundle\Grid\DeviceLog\DeviceLogGridFactory;
use AppBundle\Grid\EntityLog\EntityLogGridFactory;
use AppBundle\Grid\Site\SiteGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Map;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/site")
 */
class SiteController extends BaseController
{
    /**
     * @Route("", name="site_index")
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(SiteGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'site/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="site_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="site_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Site $site): Response
    {
        $this->notDeleted($site);

        return $this->handleNewEditAction($request, $site);
    }

    /**
     * @Route("/{id}", name="site_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Request $request, Site $site): Response
    {
        $this->notDeleted($site);

        if ($this->isPermissionGranted(Permission::VIEW, DeviceController::class)) {
            $deviceLogGrid = $this->get(DeviceLogGridFactory::class)->create(null, $site);
            if ($parameters = $deviceLogGrid->processAjaxRequest($request)) {
                return $this->createAjaxResponse($parameters);
            }

            $entityLogGrid = $this->get(EntityLogGridFactory::class)->create($site);
            if ($parameters = $entityLogGrid->processAjaxRequest($request)) {
                return $this->createAjaxResponse($parameters);
            }
        }

        return $this->render(
            'site/show.html.twig',
            [
                'site' => $site,
                'deviceLogGrid' => $deviceLogGrid ?? null,
                'entityLogGrid' => $entityLogGrid ?? null,
            ]
        );
    }

    /**
     * @Route("/{id}/devices", name="site_show_devices", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showDevicesAction(Request $request, Site $site): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, DeviceController::class);
        $this->notDeleted($site);
        $grid = $this->get(SiteDeviceGridFactory::class)->create($site);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'site/show_devices.html.twig',
            [
                'site' => $site,
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="site_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Site $site): Response
    {
        $this->notDeleted($site);

        if (! $this->get(SiteFacade::class)->handleDelete($site)) {
            $this->addTranslatedFlash('error', 'Site with related devices cannot be deleted.');

            return $this->redirectToRoute(
                'site_show',
                [
                    'id' => $site->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Site has been deleted.');

        return $this->redirectToRoute('site_index');
    }

    private function handleNewEditAction(Request $request, Site $site = null): Response
    {
        $isEdit = true;
        if ($site === null) {
            $site = new Site();
            $isEdit = false;
        }

        $from = $this->createForm(SiteType::class, $site);
        $from->handleRequest($request);

        if ($from->isSubmitted() && $from->isValid()) {
            $this->em->persist($site);
            $this->em->flush();

            if ($isEdit) {
                $this->addTranslatedFlash('success', 'Site has been saved.');
            } else {
                $this->addTranslatedFlash('success', 'Site has been created.');
            }

            return $this->redirectToRoute(
                'site_show',
                [
                    'id' => $site->getId(),
                ]
            );
        }

        if ($isEdit) {
            $gpsLat = $site->getGpsLat();
            $gpsLon = $site->getGpsLon();
            $zoom = Map::DEFAULT_ZOOM;
        } else {
            $zoom = 1;
            $gpsLat = 0;
            $gpsLon = 0;
        }

        return $this->render(
            'site/edit.html.twig',
            [
                'site' => $site,
                'form' => $from->createView(),
                'zoom' => $zoom,
                'gpsLat' => $gpsLat,
                'gpsLon' => $gpsLon,
                'isEdit' => $isEdit,
            ]
        );
    }
}
