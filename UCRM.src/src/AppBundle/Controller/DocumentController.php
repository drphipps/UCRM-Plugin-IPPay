<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Uploader\DocumentsUploadListener;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Document;
use AppBundle\Facade\DocumentFacade;
use AppBundle\Grid\Document\DocumentGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/documents")
 */
class DocumentController extends BaseController
{
    const FILTER_ALL = 'all';
    const FILTER_DOCUMENTS = 'documents';
    const FILTER_IMAGES = 'images';
    const FILTER_OTHERS = 'others';

    /**
     * @Route(
     *     "/{id}/{filterType}",
     *     name="documents_index",
     *     defaults={"filterType" = "all"},
     *     requirements={
     *         "id": "\d+",
     *         "filterType": "all|images|documents|others"
     *     }
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request, Client $client, string $filterType): Response
    {
        $grid = $this->get(DocumentGridFactory::class)->create($client, $filterType);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'document/index.html.twig',
            [
                'client' => $client,
                'grid' => $grid,
                'filterType' => $filterType,
                'filterTypes' => [
                    self::FILTER_ALL => 'All files',
                    self::FILTER_DOCUMENTS => 'Documents',
                    self::FILTER_IMAGES => 'Images',
                    self::FILTER_OTHERS => 'Others',
                ],
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/download", name="documents_download", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadAction(Document $document): Response
    {
        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $this->getParameter('kernel.root_dir') . $document->getPath(),
            $document->getName()
        );
    }

    /**
     * @Route("/{id}/new", name="documents_new", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function newAction(Client $client): Response
    {
        $this->notDeleted($client);

        $uploadCsrfToken = $this->get('security.csrf.token_manager')->getToken(DocumentsUploadListener::CSRF_TOKEN_ID);

        return $this->render(
            'document/new.html.twig',
            [
                'client' => $client,
                'dropzoneType' => DocumentsUploadListener::TYPE,
                'dropzoneFormAppend' => [
                    DocumentsUploadListener::ARG_CLIENT_ID => $client->getId(),
                ],
                'uploadCsrfTokenFieldName' => $this->getParameter('form.type_extension.csrf.field_name'),
                'uploadCsrfTokenValue' => $uploadCsrfToken,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="documents_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Document $document): RedirectResponse
    {
        $clientId = $document->getClient()->getId();

        $this->get(DocumentFacade::class)->handleDelete($document);

        $this->addTranslatedFlash('success', 'Document has been deleted.');

        return $this->redirectToRoute(
            'documents_index',
            [
                'id' => $clientId,
            ]
        );
    }
}
