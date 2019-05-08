<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\DataProvider\ProformaInvoiceTemplateDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialTemplateFacade;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Form\FinancialTemplateImportType;
use AppBundle\Form\FinancialTemplateType;
use AppBundle\Grid\ProformaInvoiceTemplate\ProformaInvoiceTemplateGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Financial\FinancialTemplateFileManager;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Util\Strings;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/system/customization/proforma-invoice-templates")
 */
class ProformaInvoiceTemplateController extends BaseController
{
    /**
     * @Route("", name="proforma_invoice_template_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Proforma invoices", path="System -> Customization -> Proforma invoices", extra={"Proforma invoice templates"})
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ProformaInvoiceTemplateGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'proforma_invoice_template/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="proforma_invoice_template_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        try {
            $htmlSource = $this->get(FinancialTemplateRenderer::class)->getPreviewHtml($proformaInvoiceTemplate);
        } catch (TemplateRenderException $exception) {
            $htmlSource = $exception->getMessageForView();
        } catch (\Dompdf\Exception $exception) {
            $htmlSource = $exception->getMessage();
        }

        return $this->render(
            'proforma_invoice_template/show.html.twig',
            [
                'proformaInvoiceTemplate' => $proformaInvoiceTemplate,
                'htmlSource' => $htmlSource,
                'isUsedOnOrganization' => $this->get(ProformaInvoiceTemplateDataProvider::class)
                    ->isUsedOnOrganization($proformaInvoiceTemplate),
            ]
        );
    }

    /**
     * @Route("/{id}/preview", name="proforma_invoice_template_preview", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function previewAction(Request $request, ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getPreviewPdf($proformaInvoiceTemplate);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            if ($request->get('download')) {
                throw $this->createNotFoundException();
            }

            return $this->render(
                'proforma_invoice_template/render_errors.html.twig',
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
     * @Route("/new", name="proforma_invoice_template_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="proforma_invoice_template_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        return $this->handleNewEditAction($request, $proformaInvoiceTemplate);
    }

    /**
     * @Route("/{id}/clone", name="proforma_invoice_template_clone", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cloneAction(ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        $cloned = $this->get(FinancialTemplateFacade::class)->handleClone($proformaInvoiceTemplate);
        $this->addTranslatedFlash('success', 'Proforma invoice template has been created.');

        return $this->redirectToRoute(
            'proforma_invoice_template_edit',
            [
                'id' => $cloned->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/export", name="proforma_invoice_template_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function exportAction(ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        try {
            $zip = $this->get(FinancialTemplateFacade::class)->handleExport($proformaInvoiceTemplate);
        } catch (TemplateImportExportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('proforma_invoice_template_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $zip,
            sprintf('template-%s.zip', Strings::slugify($proformaInvoiceTemplate->getName())),
            'application/zip'
        );
    }

    /**
     * @Route("/import", name="proforma_invoice_template_import")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function importAction(Request $request): Response
    {
        $templateImportData = new TemplateImportData();
        $form = $this->createForm(
            FinancialTemplateImportType::class,
            $templateImportData,
            [
                'action' => $this->generateUrl('proforma_invoice_template_import'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $proformaInvoiceTemplate = $this->get(FinancialTemplateFacade::class)->handleImportProformaInvoiceTemplate($templateImportData);

                $this->addTranslatedFlash('success', 'Proforma invoice template has been imported.');

                return $this->createAjaxRedirectResponse(
                    'proforma_invoice_template_show',
                    [
                        'id' => $proformaInvoiceTemplate->getId(),
                    ]
                );
            } catch (TemplateImportExportException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'proforma_invoice_template/import.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="proforma_invoice_template_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ProformaInvoiceTemplate $proformaInvoiceTemplate): Response
    {
        $this->notDeleted($proformaInvoiceTemplate);

        if ($this->get(FinancialTemplateFacade::class)->handleDelete($proformaInvoiceTemplate)) {
            $this->addTranslatedFlash('success', 'Proforma invoice template has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Proforma invoice template could not be deleted.');
        }

        return $this->redirectToRoute('proforma_invoice_template_index');
    }

    private function handleNewEditAction(Request $request, ?ProformaInvoiceTemplate $proformaInvoiceTemplate = null): Response
    {
        $proformaInvoiceTemplate = $proformaInvoiceTemplate ?? new ProformaInvoiceTemplate();
        $isEdit = (bool) $proformaInvoiceTemplate->getId();
        if ($proformaInvoiceTemplate->getOfficialName()) {
            $this->addTranslatedFlash('error', 'Official proforma invoice templates cannot be edited.');

            return $this->redirectToRoute(
                'proforma_invoice_template_show',
                [
                    'id' => $proformaInvoiceTemplate->getId(),
                ]
            );
        }

        $form = $this->createForm(FinancialTemplateType::class, $proformaInvoiceTemplate);

        if ($isEdit) {
            $templateFileManager = $this->get(FinancialTemplateFileManager::class);
            try {
                $form->get('twig')->setData(
                    $templateFileManager->getSource($proformaInvoiceTemplate, FinancialTemplateFileManager::TWIG_FILENAME)
                );
                $form->get('css')->setData(
                    $templateFileManager->getSource($proformaInvoiceTemplate, FinancialTemplateFileManager::CSS_FILENAME)
                );
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->processForm($proformaInvoiceTemplate, $form);

            return $this->redirectToRoute(
                'proforma_invoice_template_show',
                [
                    'id' => $proformaInvoiceTemplate->getId(),
                ]
            );
        }

        $customAttributeDataProvider = $this->get(CustomAttributeDataProvider::class);

        return $this->render(
            'proforma_invoice_template/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'proformaInvoiceTemplate' => $proformaInvoiceTemplate,
                'clientAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT),
                'invoiceAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_INVOICE),
            ]
        );
    }

    private function processForm(ProformaInvoiceTemplate $proformaInvoiceTemplate, FormInterface $form): void
    {
        if ($proformaInvoiceTemplate->getId()) {
            $this->get(FinancialTemplateFacade::class)->handleUpdate(
                $proformaInvoiceTemplate,
                $form->get('twig')->getData() ?? '',
                $form->get('css')->getData() ?? ''
            );

            $this->addTranslatedFlash('success', 'Proforma invoice template has been edited.');

            return;
        }

        $this->get(FinancialTemplateFacade::class)->handleCreate(
            $proformaInvoiceTemplate,
            $form->get('twig')->getData() ?? '',
            $form->get('css')->getData() ?? ''
        );

        $this->addTranslatedFlash('success', 'Proforma invoice template has been created.');
    }
}
