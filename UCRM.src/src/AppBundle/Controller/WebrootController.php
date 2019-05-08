<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Facade\WebrootUploadFacade;
use AppBundle\FileManager\WebrootFileManager;
use AppBundle\Form\Data\WebrootUploadData;
use AppBundle\Form\WebrootUploadType;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/webroot")
 */
class WebrootController extends BaseController
{
    /**
     * @Route("", name="webroot_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Webroot", path="System -> Tools -> Webroot")
     */
    public function indexAction(Request $request): Response
    {
        $webrootUploadData = new WebrootUploadData();
        $form = $this->createForm(WebrootUploadType::class, $webrootUploadData);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (Helpers::isDemo()) {
                    $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
                } else {
                    $this->get(WebrootUploadFacade::class)->handleWebrootUpload($webrootUploadData->file);
                    $this->addTranslatedFlash('success', 'File uploaded into webroot.');
                }
            } catch (FileException $e) {
                $this->addTranslatedFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('webroot_index');
        }

        return $this->render(
            'webroot/index.html.twig',
            [
                'form' => $form->createView(),
                'files' => $this->get(WebrootFileManager::class)->getFiles(),
            ]
        );
    }

    /**
     * @Route("/delete/{fileName}", name="webroot_delete")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function deleteFileAction(string $fileName): Response
    {
        $webrootFileManager = $this->get(WebrootFileManager::class);
        $webrootFileManager->deleteFile($fileName);
        $this->addTranslatedFlash('success', 'File has been deleted.');

        $this->invalidateTemplate(
            'webroot-files',
            'webroot/components/files.html.twig',
            [
                'files' => $webrootFileManager->getFiles(),
            ]
        );

        return $this->createAjaxResponse();
    }
}
