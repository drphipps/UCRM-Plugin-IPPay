<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Import\DataProvider\ClientImportPreviewDataProvider;
use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Component\Import\Facade\ClientImportItemFacade;
use AppBundle\Component\Import\Facade\ClientImportPreviewFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\Import\ServiceImportItem;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/import/clients")
 * @PermissionControllerName(ClientImportController::class)
 */
class ClientImportPreviewController extends BaseController
{
    /**
     * @var ClientImportPreviewDataProvider
     */
    private $clientImportPreviewDataProvider;

    /**
     * @var ClientImportItemFacade
     */
    private $clientImportItemFacade;

    /**
     * @var ClientImportPreviewFacade
     */
    private $clientImportPreviewFacade;

    public function __construct(
        ClientImportPreviewDataProvider $clientImportPreviewDataProvider,
        ClientImportItemFacade $clientImportItemFacade,
        ClientImportPreviewFacade $clientImportPreviewFacade
    ) {
        $this->clientImportPreviewDataProvider = $clientImportPreviewDataProvider;
        $this->clientImportItemFacade = $clientImportItemFacade;
        $this->clientImportPreviewFacade = $clientImportPreviewFacade;
    }

    /**
     * @Route("/preview/{id}", name="import_clients_preview", requirements={"id": "%uuid_regex%"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function previewClientsAction(ClientImport $clientImport): Response
    {
        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            $this->addTranslatedFlash(
                'error',
                'The import process is already started, this action is no longer possible.'
            );

            return $this->redirectToRoute('import_clients_index');
        }

        return $this->render(
            'import_clients/preview.html.twig',
            [
                'import' => $clientImport,
                'preview' => $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED)
                    ? $this->clientImportPreviewDataProvider->get($clientImport)
                    : null,
            ]
        );
    }

    /**
     * @Route("/preview/revalidate/{id}", name="import_clients_preview_revalidate", requirements={"id": "%uuid_regex%"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function revalidateAction(ClientImport $clientImport): Response
    {
        try {
            $this->clientImportPreviewFacade->enqueueRevalidation($clientImport);
        } catch (ImportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->createAjaxResponse();
        }

        $this->invalidateTemplate(
            'import-preview-container',
            'import_clients/components/loading.html.twig',
            [
                'import' => $clientImport,
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/preview/ready/{id}", name="import_clients_preview_ready", requirements={"id": "%uuid_regex%"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function renderPreviewWhenReadyAction(ClientImport $clientImport): Response
    {
        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            $this->addTranslatedFlash(
                'error',
                'The import process is already started, this action is no longer possible.'
            );

            return $this->createAjaxRedirectResponse('import_clients_index');
        }

        $isValidated = $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED);
        if ($isValidated) {
            $this->invalidateTemplate(
                'import-preview-container',
                'import_clients/components/preview.html.twig',
                [
                    'import' => $clientImport,
                    'preview' => $this->clientImportPreviewDataProvider->get($clientImport),
                ]
            );
        }

        return $this->createAjaxResponse(
            [
                'ready' => $isValidated,
            ]
        );
    }

    /**
     * @Route(
     *     "/preview/mark-client/{id}",
     *     name="import_clients_mark_client",
     *     requirements={"id": "%uuid_regex%"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function markClientItemForImport(Request $request, ClientImportItem $clientImportItem): Response
    {
        if ($clientImportItem->getImport()->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            $this->addTranslatedFlash(
                'error',
                'The import process is already started, this action is no longer possible.'
            );

            return $this->createAjaxRedirectResponse('import_clients_index');
        }

        if (! $request->query->has('doImport')) {
            throw $this->createNotFoundException();
        }

        try {
            $this->clientImportItemFacade->markClientItemForImport(
                $clientImportItem,
                $request->query->getBoolean('doImport')
            );
        } catch (ImportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->createAjaxResponse();
        }

        $this->invalidateTemplate(
            'preview-item-do-import__client__' . $clientImportItem->getId(),
            'import_clients/components/clients/preview_item_do_import.html.twig',
            [
                'clientItem' => $clientImportItem,
                'serviceItem' => null,
            ],
            true
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route(
     *     "/preview/mark-service/{id}",
     *     name="import_clients_mark_service",
     *     requirements={"id": "%uuid_regex%"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function markServiceItemForImport(Request $request, ServiceImportItem $serviceImportItem): Response
    {
        if ($serviceImportItem->getImportItem()->getImport()->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            $this->addTranslatedFlash(
                'error',
                'The import process is already started, this action is no longer possible.'
            );

            return $this->createAjaxRedirectResponse('import_clients_index');
        }

        if (! $request->query->has('doImport')) {
            throw $this->createNotFoundException();
        }

        try {
            $this->clientImportItemFacade->markServiceItemForImport(
                $serviceImportItem,
                $request->query->getBoolean('doImport')
            );
        } catch (ImportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->createAjaxResponse();
        }

        $this->invalidateTemplate(
            'preview-item-do-import__service__' . $serviceImportItem->getId(),
            'import_clients/components/clients/preview_item_do_import.html.twig',
            [
                'clientItem' => $serviceImportItem->getImportItem(),
                'serviceItem' => $serviceImportItem,
            ],
            true
        );

        return $this->createAjaxResponse();
    }
}
