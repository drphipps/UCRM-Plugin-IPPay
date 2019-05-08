<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Download;
use AppBundle\Facade\DownloadFacade;
use AppBundle\Grid\Download\DownloadGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/downloads")
 */
class DownloadController extends BaseController
{
    /**
     * @Route("", name = "download_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Downloads", path="System -> Tools -> Downloads")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(DownloadGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'download/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}/download", name = "download_download", requirements = {"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadAction(Download $download): Response
    {
        if ($download->getStatus() !== Download::STATUS_READY) {
            $this->addTranslatedFlash('error', 'Download is not yet ready or failed to generate.');

            return $this->redirectToRoute('download_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $this->getParameter('kernel.root_dir') . $download->getPath()
        );
    }

    /**
     * @Route("/{id}/delete", name = "download_delete", requirements = {"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Download $download): Response
    {
        $this->get(DownloadFacade::class)->handleDelete($download);

        $this->addTranslatedFlash('success', 'Download has been deleted.');

        return $this->redirectToRoute('download_index');
    }
}
