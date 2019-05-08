<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\AccountStatementTemplateDataProvider;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialTemplateFacade;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Form\FinancialTemplateImportType;
use AppBundle\Form\FinancialTemplateType;
use AppBundle\Grid\AccountStatementTemplate\AccountStatementTemplateGridFactory;
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
 * @Route("/system/customization/account-statement-templates")
 */
class AccountStatementTemplateController extends BaseController
{
    /**
     * @Route("", name="account_statement_template_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(
     *     heading="Account statements",
     *     path="System -> Customization -> Account statements",
     *     extra={"Account statement templates"}
     * )
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(AccountStatementTemplateGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'account_statement_template/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="account_statement_template_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        try {
            $htmlSource = $this->get(FinancialTemplateRenderer::class)->getPreviewHtml($accountStatementTemplate);
        } catch (TemplateRenderException $exception) {
            $htmlSource = $exception->getMessageForView();
        } catch (\Dompdf\Exception $exception) {
            $htmlSource = $exception->getMessage();
        }

        return $this->render(
            'account_statement_template/show.html.twig',
            [
                'accountStatementTemplate' => $accountStatementTemplate,
                'htmlSource' => $htmlSource,
                'isUsedOnOrganization' => $this->get(AccountStatementTemplateDataProvider::class)
                    ->isUsedOnOrganization($accountStatementTemplate),
            ]
        );
    }

    /**
     * @Route("/{id}/preview", name="account_statement_template_preview", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function previewAction(Request $request, AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getPreviewPdf($accountStatementTemplate);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            if ($request->get('download')) {
                throw $this->createNotFoundException();
            }

            return $this->render(
                'account_statement_template/render_errors.html.twig',
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
     * @Route("/new", name="account_statement_template_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="account_statement_template_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        return $this->handleNewEditAction($request, $accountStatementTemplate);
    }

    /**
     * @Route("/{id}/clone", name="account_statement_template_clone", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cloneAction(AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        $cloned = $this->get(FinancialTemplateFacade::class)->handleClone($accountStatementTemplate);
        $this->addTranslatedFlash('success', 'Template has been created.');

        return $this->redirectToRoute(
            'account_statement_template_edit',
            [
                'id' => $cloned->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/export", name="account_statement_template_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function exportAction(AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        try {
            $zip = $this->get(FinancialTemplateFacade::class)->handleExport($accountStatementTemplate);
        } catch (TemplateImportExportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('account_statement_template_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $zip,
            sprintf('template-%s.zip', Strings::slugify($accountStatementTemplate->getName())),
            'application/zip'
        );
    }

    /**
     * @Route("/import", name="account_statement_template_import")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function importAction(Request $request): Response
    {
        $accountStatementTemplateImport = new TemplateImportData();
        $form = $this->createForm(
            FinancialTemplateImportType::class,
            $accountStatementTemplateImport,
            [
                'action' => $this->generateUrl('account_statement_template_import'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $accountStatementTemplate = $this->get(
                    FinancialTemplateFacade::class
                )->handleImportAccountStatementTemplate(
                    $accountStatementTemplateImport
                );

                $this->addTranslatedFlash('success', 'Template has been imported.');

                return $this->createAjaxRedirectResponse(
                    'account_statement_template_show',
                    [
                        'id' => $accountStatementTemplate->getId(),
                    ]
                );
            } catch (TemplateImportExportException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'account_statement_template/import.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="account_statement_template_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(AccountStatementTemplate $accountStatementTemplate): Response
    {
        $this->notDeleted($accountStatementTemplate);

        if ($this->get(FinancialTemplateFacade::class)->handleDelete($accountStatementTemplate)) {
            $this->addTranslatedFlash('success', 'Template has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Template could not be deleted.');
        }

        return $this->redirectToRoute('account_statement_template_index');
    }

    private function handleNewEditAction(
        Request $request,
        ?AccountStatementTemplate $accountStatementTemplate = null
    ): Response {
        $accountStatementTemplate = $accountStatementTemplate ?? new AccountStatementTemplate();
        $isEdit = (bool) $accountStatementTemplate->getId();
        if ($accountStatementTemplate->getOfficialName()) {
            $this->addTranslatedFlash('error', 'Official templates cannot be edited.');

            return $this->redirectToRoute(
                'account_statement_template_show',
                [
                    'id' => $accountStatementTemplate->getId(),
                ]
            );
        }

        $form = $this->createForm(FinancialTemplateType::class, $accountStatementTemplate);

        if ($isEdit) {
            $templateFileManager = $this->get(FinancialTemplateFileManager::class);
            try {
                $form->get('twig')->setData(
                    $templateFileManager->getSource(
                        $accountStatementTemplate,
                        FinancialTemplateFileManager::TWIG_FILENAME
                    )
                );
                $form->get('css')->setData(
                    $templateFileManager->getSource(
                        $accountStatementTemplate,
                        FinancialTemplateFileManager::CSS_FILENAME
                    )
                );
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(FinancialTemplateFacade::class)->handleUpdate(
                    $accountStatementTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Template has been edited.');
            } else {
                $this->get(FinancialTemplateFacade::class)->handleCreate(
                    $accountStatementTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Template has been created.');
            }

            return $this->redirectToRoute(
                'account_statement_template_show',
                [
                    'id' => $accountStatementTemplate->getId(),
                ]
            );
        }

        return $this->render(
            'account_statement_template/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'accountStatementTemplate' => $accountStatementTemplate,
                'clientAttributes' => $this->get(CustomAttributeDataProvider::class)
                    ->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT),
            ]
        );
    }
}
