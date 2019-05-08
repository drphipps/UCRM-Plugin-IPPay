<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\DataProvider\PaymentReceiptTemplateDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\PaymentReceiptTemplateFacade;
use AppBundle\FileManager\PaymentReceiptTemplateFileManager;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Form\FinancialTemplateImportType;
use AppBundle\Form\PaymentReceiptTemplateType;
use AppBundle\Grid\PaymentReceiptTemplate\PaymentReceiptTemplateGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;
use AppBundle\Util\Strings;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/system/customization/receipt-templates")
 */
class PaymentReceiptTemplateController extends BaseController
{
    /**
     * @Route("", name="payment_receipt_template_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Receipts", path="System -> Customization -> Receipt templates", extra={"Receipt templates"})
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(PaymentReceiptTemplateGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'payment_receipt_template/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="payment_receipt_template_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        try {
            $htmlSource = $this->get(PaymentReceiptTemplateRenderer::class)->getPreviewHtml($paymentReceiptTemplate);
        } catch (TemplateRenderException $exception) {
            $htmlSource = $exception->getMessageForView();
        } catch (\Dompdf\Exception $exception) {
            $htmlSource = $exception->getMessage();
        }

        return $this->render(
            'payment_receipt_template/show.html.twig',
            [
                'paymentReceiptTemplate' => $paymentReceiptTemplate,
                'htmlSource' => $htmlSource,
                'isUsedOnOrganization' => $this->get(PaymentReceiptTemplateDataProvider::class)
                    ->isUsedOnOrganization($paymentReceiptTemplate),
            ]
        );
    }

    /**
     * @Route("/{id}/preview", name="payment_receipt_template_preview", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function previewAction(Request $request, PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        try {
            $pdf = $this->get(PaymentReceiptTemplateRenderer::class)->getPreviewPdf($paymentReceiptTemplate);
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            if ($request->get('download')) {
                throw $this->createNotFoundException();
            }

            return $this->render(
                'payment_receipt_template/render_errors.html.twig',
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
     * @Route("/new", name="payment_receipt_template_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="payment_receipt_template_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        return $this->handleNewEditAction($request, $paymentReceiptTemplate);
    }

    /**
     * @Route("/{id}/clone", name="payment_receipt_template_clone", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cloneAction(PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        $cloned = $this->get(PaymentReceiptTemplateFacade::class)->handleClone($paymentReceiptTemplate);
        $this->addTranslatedFlash('success', 'Receipt template has been created.');

        return $this->redirectToRoute(
            'payment_receipt_template_edit',
            [
                'id' => $cloned->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/export", name="payment_receipt_template_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function exportAction(PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        try {
            $zip = $this->get(PaymentReceiptTemplateFacade::class)->handleExport($paymentReceiptTemplate);
        } catch (TemplateImportExportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute('payment_receipt_template_index');
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $zip,
            sprintf('template-%s.zip', Strings::slugify($paymentReceiptTemplate->getName())),
            'application/zip'
        );
    }

    /**
     * @Route("/import", name="payment_receipt_template_import")
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
                'action' => $this->generateUrl('payment_receipt_template_import'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $paymentReceiptTemplate = $this->get(PaymentReceiptTemplateFacade::class)->handleImport($importData);

                $this->addTranslatedFlash('success', 'Receipt template has been imported.');

                return $this->createAjaxRedirectResponse(
                    'payment_receipt_template_show',
                    [
                        'id' => $paymentReceiptTemplate->getId(),
                    ]
                );
            } catch (TemplateImportExportException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'payment_receipt_template/import.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="payment_receipt_template_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(PaymentReceiptTemplate $paymentReceiptTemplate): Response
    {
        $this->notDeleted($paymentReceiptTemplate);

        if ($this->get(PaymentReceiptTemplateFacade::class)->handleDelete($paymentReceiptTemplate)) {
            $this->addTranslatedFlash('success', 'Receipt template has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Receipt template could not be deleted.');
        }

        return $this->redirectToRoute('payment_receipt_template_index');
    }

    private function handleNewEditAction(Request $request, ?PaymentReceiptTemplate $paymentReceiptTemplate = null): Response
    {
        $paymentReceiptTemplate = $paymentReceiptTemplate ?? new PaymentReceiptTemplate();
        $isEdit = (bool) $paymentReceiptTemplate->getId();
        if ($paymentReceiptTemplate->getOfficialName()) {
            $this->addTranslatedFlash('error', 'Official receipt templates cannot be edited.');

            return $this->redirectToRoute(
                'payment_receipt_template_show',
                [
                    'id' => $paymentReceiptTemplate->getId(),
                ]
            );
        }

        $form = $this->createForm(PaymentReceiptTemplateType::class, $paymentReceiptTemplate);

        if ($isEdit) {
            $templateFileManager = $this->get(PaymentReceiptTemplateFileManager::class);
            try {
                $form->get('twig')->setData(
                    $templateFileManager->getSource($paymentReceiptTemplate, PaymentReceiptTemplateFileManager::TWIG_FILENAME)
                );
                $form->get('css')->setData(
                    $templateFileManager->getSource($paymentReceiptTemplate, PaymentReceiptTemplateFileManager::CSS_FILENAME)
                );
            } catch (FileNotFoundException $exception) {
                $this->addTranslatedFlash('error', $exception->getMessage());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isEdit) {
                $this->get(PaymentReceiptTemplateFacade::class)->handleUpdate(
                    $paymentReceiptTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Receipt template has been edited.');
            } else {
                $this->get(PaymentReceiptTemplateFacade::class)->handleCreate(
                    $paymentReceiptTemplate,
                    $form->get('twig')->getData() ?? '',
                    $form->get('css')->getData() ?? ''
                );

                $this->addTranslatedFlash('success', 'Receipt template has been created.');
            }

            return $this->redirectToRoute(
                'payment_receipt_template_show',
                [
                    'id' => $paymentReceiptTemplate->getId(),
                ]
            );
        }

        $customAttributeDataProvider = $this->get(CustomAttributeDataProvider::class);

        return $this->render(
            'payment_receipt_template/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'paymentReceiptTemplate' => $paymentReceiptTemplate,
                'clientAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_CLIENT),
                'invoiceAttributes' => $customAttributeDataProvider->getByAttributeType(CustomAttribute::ATTRIBUTE_TYPE_INVOICE),
            ]
        );
    }
}
