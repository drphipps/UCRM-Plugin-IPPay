<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\DataProvider\InvoiceTemplateDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialTemplateFacade;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Form\FinancialTemplateImportType;
use AppBundle\Form\FinancialTemplateType;
use AppBundle\Grid\InvoiceTemplate\InvoiceTemplateGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Financial\FinancialTemplateFileManager;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Util\Strings;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/system/customization/invoice-templates")
 */
class InvoiceTemplateController extends BaseController
{
    /**
     * @Route("", name="invoice_template_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Invoices", path="System -> Customization -> Invoices", extra={"Invoice templates"})
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(InvoiceTemplateGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'invoice_template/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="invoice_template_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        try {
            $htmlSource = $this->get(FinancialTemplateRenderer::class)->getPreviewHtml($invoiceTemplate);
        } catch (TemplateRenderException $exception) {
            $htmlSource = $exception->getMessageForView();
        } catch (\Dompdf\Exception $exception) {
            $htmlSource = $exception->getMessage();
        }

        return $this->render(
            'invoice_template/show.html.twig',
            [
                'invoiceTemplate' => $invoiceTemplate,
                'htmlSource' => $htmlSource,
                'isUsedOnOrganization' => $this->get(InvoiceTemplateDataProvider::class)
                    ->isUsedOnOrganization($invoiceTemplate),
            ]
        );
    }

    /**
     * @Route("/{id}/preview", name="invoice_template_preview", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function previewAction(Request $request, InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getPreviewPdf($invoiceTemplate);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            if ($request->get('download')) {
                throw $this->createNotFoundException();
            }

            return $this->render(
                'invoice_template/render_errors.html.twig',
                [
                    'exception' => $exception,
                ]
            );
        }

        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');

        $disposition = $response->headers->makeDisposition(
            $request->get('download')
                ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
                : ResponseHeaderBag::DISPOSITION_INLINE,
            'preview.pdf'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/new", name="invoice_template_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="invoice_template_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        return $this->handleNewEditAction($request, $invoiceTemplate);
    }

    /**
     * @Route("/{id}/clone", name="invoice_template_clone", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cloneAction(InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        $cloned = $this->get(FinancialTemplateFacade::class)->handleClone($invoiceTemplate);
        $this->addTranslatedFlash('success', 'Invoice template has been created.');

        return $this->redirectToRoute(
            'invoice_template_edit',
            [
                'id' => $cloned->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/export", name="invoice_template_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function exportAction(InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        try {
            $zip = $this->get(FinancialTemplateFacade::class)->handleExport($invoiceTemplate);
        } catch (TemplateImportExportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('invoice_template_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $zip,
            sprintf('template-%s.zip', Strings::slugify($invoiceTemplate->getName())),
            'application/zip'
        );
    }

    /**
     * @Route("/import", name="invoice_template_import")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function importAction(Request $request): Response
    {
        $invoiceTemplateImport = new TemplateImportData();
        $form = $this->createForm(
            FinancialTemplateImportType::class,
            $invoiceTemplateImport,
            [
                'action' => $this->generateUrl('invoice_template_import'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $invoiceTemplate = $this->get(FinancialTemplateFacade::class)->handleImportInvoiceTemplate($invoiceTemplateImport);

                $this->addTranslatedFlash('success', 'Invoice template has been imported.');

                return $this->createAjaxRedirectResponse(
                    'invoice_template_show',
                    [
                        'id' => $invoiceTemplate->getId(),
                    ]
                );
            } catch (TemplateImportExportException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'invoice_template/import.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="invoice_template_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(InvoiceTemplate $invoiceTemplate): Response
    {
        $this->notDeleted($invoiceTemplate);

        if ($this->get(FinancialTemplateFacade::class)->handleDelete($invoiceTemplate)) {
            $this->addTranslatedFlash('success', 'Invoice template has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Invoice template could not be deleted.');
        }

        return $this->redirectToRoute('invoice_template_index');
    }

    private function handleNewEditAction(Request $request, ?InvoiceTemplate $invoiceTemplate = null): Response
    {
        $invoiceTemplate = $invoiceTemplate ?? new InvoiceTemplate();
        $isEdit = (bool) $invoiceTemplate->getId();
        if ($invoiceTemplate->getOfficialName()) {
            $this->addTranslatedFlash('error', 'Official invoice templates cannot be edited.');

            return $this->redirectToRoute(
                'invoice_template_show',
                [
                    'id' => $invoiceTemplate->getId(),
                ]
            );
        }

        $form = $this->createForm(FinancialTemplateType::class, $invoiceTemplate);

        if ($isEdit) {
            $templateFileManager = $this->get(FinancialTemplateFileManager::class);
            try {
                $form->get('twig')->setData(
                    $templateFileManager->getSource($invoiceTemplate, FinancialTemplateFileManager::TWIG_FILENAME)
                );
                $form->get('css')->setData(
                    $templateFileManager->getSource($invoiceTemplate, FinancialTemplateFileManager::CSS_FILENAME)
                );
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(FinancialTemplateFacade::class)->handleUpdate(
                    $invoiceTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Invoice template has been edited.');
            } else {
                $this->get(FinancialTemplateFacade::class)->handleCreate(
                    $invoiceTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Invoice template has been created.');
            }

            return $this->redirectToRoute(
                'invoice_template_show',
                [
                    'id' => $invoiceTemplate->getId(),
                ]
            );
        }

        $customAttributeDataProvider = $this->get(CustomAttributeDataProvider::class);

        return $this->render(
            'invoice_template/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'invoiceTemplate' => $invoiceTemplate,
                'clientAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT),
                'invoiceAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_INVOICE),
            ]
        );
    }
}
