<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\DataProvider\QuoteTemplateDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialTemplateFacade;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Form\FinancialTemplateImportType;
use AppBundle\Form\FinancialTemplateType;
use AppBundle\Grid\QuoteTemplate\QuoteTemplateGridFactory;
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
 * @Route("/system/customization/quote-templates")
 */
class QuoteTemplateController extends BaseController
{
    /**
     * @Route("", name="quote_template_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Quotes", path="System -> Customization -> Quote templates", extra={"Quote templates"})
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(QuoteTemplateGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'quote_template/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="quote_template_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(QuoteTemplate $quoteTemplate): Response
    {
        $this->notDeleted($quoteTemplate);

        try {
            $htmlSource = $this->get(FinancialTemplateRenderer::class)->getPreviewHtml($quoteTemplate);
        } catch (TemplateRenderException $exception) {
            $htmlSource = $exception->getMessageForView();
        } catch (\Dompdf\Exception $exception) {
            $htmlSource = $exception->getMessage();
        }

        return $this->render(
            'quote_template/show.html.twig',
            [
                'quoteTemplate' => $quoteTemplate,
                'htmlSource' => $htmlSource,
                'isUsedOnOrganization' => $this->get(QuoteTemplateDataProvider::class)
                    ->isUsedOnOrganization($quoteTemplate),
            ]
        );
    }

    /**
     * @Route("/{id}/preview", name="quote_template_preview", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function previewAction(Request $request, QuoteTemplate $QuoteTemplate): Response
    {
        $this->notDeleted($QuoteTemplate);

        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getPreviewPdf($QuoteTemplate);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            if ($request->get('download')) {
                throw $this->createNotFoundException();
            }

            return $this->render(
                'quote_template/render_errors.html.twig',
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
     * @Route("/new", name="quote_template_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="quote_template_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, QuoteTemplate $QuoteTemplate): Response
    {
        $this->notDeleted($QuoteTemplate);

        return $this->handleNewEditAction($request, $QuoteTemplate);
    }

    /**
     * @Route("/{id}/clone", name="quote_template_clone", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cloneAction(QuoteTemplate $QuoteTemplate): Response
    {
        $this->notDeleted($QuoteTemplate);

        $cloned = $this->get(FinancialTemplateFacade::class)->handleClone($QuoteTemplate);
        $this->addTranslatedFlash('success', 'Quote template has been created.');

        return $this->redirectToRoute(
            'quote_template_edit',
            [
                'id' => $cloned->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/export", name="quote_template_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function exportAction(QuoteTemplate $quoteTemplate): Response
    {
        $this->notDeleted($quoteTemplate);

        try {
            $zip = $this->get(FinancialTemplateFacade::class)->handleExport($quoteTemplate);
        } catch (TemplateImportExportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('quote_template_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $zip,
            sprintf('template-%s.zip', Strings::slugify($quoteTemplate->getName())),
            'application/zip'
        );
    }

    /**
     * @Route("/import", name="quote_template_import")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function importAction(Request $request): Response
    {
        $importData = new TemplateImportData();
        $form = $this->createForm(
            FinancialTemplateImportType::class,
            $importData,
            [
                'action' => $this->generateUrl('quote_template_import'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $quoteTemplate = $this->get(FinancialTemplateFacade::class)->handleImportQuoteTemplate($importData);

                $this->addTranslatedFlash('success', 'Quote template has been imported.');

                return $this->createAjaxRedirectResponse(
                    'quote_template_show',
                    [
                        'id' => $quoteTemplate->getId(),
                    ]
                );
            } catch (TemplateImportExportException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'quote_template/import.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="quote_template_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(QuoteTemplate $quoteTemplate): Response
    {
        $this->notDeleted($quoteTemplate);

        if ($this->get(FinancialTemplateFacade::class)->handleDelete($quoteTemplate)) {
            $this->addTranslatedFlash('success', 'Quote template has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Quote template could not be deleted.');
        }

        return $this->redirectToRoute('quote_template_index');
    }

    private function handleNewEditAction(Request $request, ?QuoteTemplate $quoteTemplate = null): Response
    {
        $quoteTemplate = $quoteTemplate ?? new QuoteTemplate();
        $isEdit = (bool) $quoteTemplate->getId();
        if ($quoteTemplate->getOfficialName()) {
            $this->addTranslatedFlash('error', 'Official quote templates cannot be edited.');

            return $this->redirectToRoute(
                'quote_template_show',
                [
                    'id' => $quoteTemplate->getId(),
                ]
            );
        }

        $form = $this->createForm(FinancialTemplateType::class, $quoteTemplate);

        if ($isEdit) {
            $templateFileManager = $this->get(FinancialTemplateFileManager::class);
            try {
                $form->get('twig')->setData(
                    $templateFileManager->getSource($quoteTemplate, FinancialTemplateFileManager::TWIG_FILENAME)
                );
                $form->get('css')->setData(
                    $templateFileManager->getSource($quoteTemplate, FinancialTemplateFileManager::CSS_FILENAME)
                );
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(FinancialTemplateFacade::class)->handleUpdate(
                    $quoteTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Quote template has been edited.');
            } else {
                $this->get(FinancialTemplateFacade::class)->handleCreate(
                    $quoteTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Quote template has been created.');
            }

            return $this->redirectToRoute(
                'quote_template_show',
                [
                    'id' => $quoteTemplate->getId(),
                ]
            );
        }

        return $this->render(
            'quote_template/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'quoteTemplate' => $quoteTemplate,
                'clientAttributes' => $this->get(CustomAttributeDataProvider::class)->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT),
            ]
        );
    }
}
