<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\OrganizationDataProvider;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Form\OrganizationType;
use AppBundle\Grid\Organization\OrganizationGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Util\Helpers;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/organizations")
 */
class OrganizationController extends BaseController
{
    /**
     * @var int
     */
    const ORGANIZATION_MAX_DEFAULT = 1;

    /**
     * @Route("", name="organization_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Organizations", path="System -> Organizations")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(OrganizationGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'organization/index.html.twig',
            [
                'organizationsGrid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="organization_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $organization = new Organization();
        $defaultCurrency = $this->em->find(Currency::class, Currency::DEFAULT_ID);
        $organization->setCurrency($defaultCurrency);

        $form = $this->createForm(
            OrganizationType::class,
            $organization,
            [
                'sandbox' => $this->isSandbox(),
                'hasFinancialEntities' => false,
                'organization' => $organization,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo() && ($organization->getFileLogo() || $organization->getFileStamp())) {
                $organization->setFileLogo(null);
                $organization->setFileStamp(null);
                $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
            }

            $this->get(OrganizationFacade::class)->handleNew($organization);

            $this->addTranslatedFlash('success', 'Organization has been created.');

            return $this->redirectToRoute('organization_show', ['id' => $organization->getId()]);
        }

        return $this->render(
            'organization/new.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="organization_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Organization $organization): Response
    {
        try {
            $paypalIpnUrl = $this->get(PublicUrlGenerator::class)->generate(
                'paypal_ipn',
                [
                    'id' => $organization->getId(),
                ],
                true
            );
            $stripeWebhookUrl = $this->get(PublicUrlGenerator::class)->generate(
                'stripe_webhook',
                [
                    'id' => $organization->getId(),
                ],
                true
            );
            $mercadoPagoIpnUrl = $this->get(PublicUrlGenerator::class)->generate(
                'mercado_pago_ipn',
                [
                    'organizationId' => $organization->getId(),
                ],
                true
            );
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        $nextFinancialNumberFactory = $this->get(NextFinancialNumberFactory::class);
        $invoiceNumberFormat = sprintf(
            '%s%s',
            $organization->getInvoiceNumberPrefix() ?? '',
            str_pad((string) $organization->getInvoiceInitNumber(), $organization->getInvoiceNumberLength(), '0')
        );
        $quoteNumberFormat = sprintf(
            '%s%s',
            $organization->getQuoteNumberPrefix() ?? '',
            str_pad((string) $organization->getQuoteInitNumber(), $organization->getQuoteNumberLength(), '0')
        );
        $proformaNumberFormat = sprintf(
            '%s%s',
            $organization->getProformaInvoiceNumberPrefix() ?? '',
            str_pad(
                (string) $organization->getProformaInvoiceInitNumber(),
                $organization->getProformaInvoiceNumberLength(),
                '0'
            )
        );
        $receiptNumberFormat = sprintf(
            '%s%s',
            $organization->getReceiptNumberPrefix() ?? '',
            str_pad((string) $organization->getReceiptInitNumber(), $organization->getReceiptNumberLength(), '0')
        );

        return $this->render(
            'organization/show.html.twig',
            [
                'organization' => $organization,
                'paypalIpnUrl' => $paypalIpnUrl ?? '',
                'stripeWebhookUrl' => $stripeWebhookUrl ?? '',
                'mercadoPagoIpnUrl' => $mercadoPagoIpnUrl ?? '',
                'invoiceNumberFormat' => $invoiceNumberFormat,
                'nextInvoiceNumber' => $nextFinancialNumberFactory->createInvoiceNumber($organization),
                'quoteNumberFormat' => $quoteNumberFormat,
                'nextQuoteNumber' => $nextFinancialNumberFactory->createQuoteNumber($organization),
                'proformaNumberFormat' => $proformaNumberFormat,
                'nextProformaNumber' => $nextFinancialNumberFactory->createProformaInvoiceNumber($organization),
                'receiptNumberFormat' => $receiptNumberFormat,
                'nextReceiptNumber' => $nextFinancialNumberFactory->createReceiptNumber($organization),
                'hasClients' => $this->get(OrganizationDataProvider::class)->hasClients($organization),
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="organization_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Organization $organization): Response
    {
        $organizationBeforeUpdate = clone $organization;
        $hasFinancialEntities = $this->get(OrganizationFacade::class)->hasRelationToFinancialEntities($organization);

        $form = $this->createForm(
            OrganizationType::class,
            $organization,
            [
                'sandbox' => $this->isSandbox(),
                'hasFinancialEntities' => $hasFinancialEntities,
                'organization' => $organizationBeforeUpdate,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (
                $hasFinancialEntities
                && $organizationBeforeUpdate->getCurrency() !== $organization->getCurrency()
            ) {
                $form->get('currency')->addError(
                    new FormError('Currency cannot be changed.')
                );
            }

            if ($form->isValid()) {
                if (Helpers::isDemo() && ($organization->getFileLogo() || $organization->getFileStamp())) {
                    $organization->setFileLogo(null);
                    $organization->setFileStamp(null);
                    $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
                }

                $this->get(OrganizationFacade::class)->handleEdit($organization);

                $this->addTranslatedFlash('success', 'Organization has been saved.');

                return $this->redirectToRoute('organization_show', ['id' => $organization->getId()]);
            }
        }

        try {
            $paypalIpnUrl = $this->get(PublicUrlGenerator::class)->generate(
                'paypal_ipn',
                [
                    'id' => $organization->getId(),
                ],
                true
            );
            $stripeWebhookUrl = $this->get(PublicUrlGenerator::class)->generate(
                'stripe_webhook',
                [
                    'id' => $organization->getId(),
                ],
                true
            );
            $mercadoPagoIpnUrl = $this->get(PublicUrlGenerator::class)->generate(
                'mercado_pago_ipn',
                [
                    'organizationId' => $organization->getId(),
                ],
                true
            );
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->render(
            'organization/edit.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'paypalIpnUrl' => $paypalIpnUrl ?? '',
                'stripeWebhookUrl' => $stripeWebhookUrl ?? '',
                'mercadoPagoIpnUrl' => $mercadoPagoIpnUrl ?? '',
                'hasClients' => $this->get(OrganizationDataProvider::class)->hasClients($organization),
            ]
        );
    }

    /**
     * @Route("/{id}/remove-logo", name="organization_remove_logo", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("POST")
     * @Permission("edit")
     * @CsrfToken(methods={"POST"})
     */
    public function removeLogoAction(Organization $organization): Response
    {
        $this->get(OrganizationFacade::class)->handleRemoveLogo($organization);

        return new Response();
    }

    /**
     * @Route("/{id}/remove-stamp", name="organization_remove_stamp", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("POST")
     * @Permission("edit")
     * @CsrfToken(methods={"POST"})
     */
    public function removeStampAction(Organization $organization): Response
    {
        $this->get(OrganizationFacade::class)->handleRemoveStamp($organization);

        return new Response();
    }

    /**
     * @Route("/{id}/default", name="organization_default", requirements={"id": "\d+"}, options={"expose": true})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     * @CsrfToken(methods={"GET", "POST"})
     */
    public function defaultAction(Request $request, Organization $organization): Response
    {
        $organizationRepository = $this->em->getRepository(Organization::class);
        $count = $organizationRepository->getCountOfSelected();
        $message = null;

        if ($count >= self::ORGANIZATION_MAX_DEFAULT && $organization->getSelected() !== true) {
            $newState = $organization->getSelected();

            $this->addTranslatedFlash(
                'error',
                sprintf(
                    $this->trans('It\'s possible to add only %d organization as default.'),
                    self::ORGANIZATION_MAX_DEFAULT
                )
            );
        } else {
            $organizationRepository->setDefault($organization->getId(), ! $organization->getSelected());
            $newState = ! $organization->getSelected();

            if ($newState) {
                $this->addTranslatedFlash('success', 'Organization has been set as default.');
            } else {
                $this->addTranslatedFlash('success', 'Organization has been set as not default.');
            }
        }

        if ($request->getMethod() === 'GET') {
            return $this->redirectToRoute('organization_show', ['id' => $organization->getId()]);
        }

        return $this->createAjaxResponse(['isDefault' => $newState]);
    }

    /**
     * @Route("/{id}/delete", name="organization_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Organization $organization): Response
    {
        if ($organization->getClients()->count() > 0) {
            $this->addTranslatedFlash('error', 'You cannot delete organization with related clients.');

            return $this->redirectToRoute('organization_index');
        }

        try {
            $this->get(OrganizationFacade::class)->handleDelete($organization);

            $this->addTranslatedFlash('success', 'Organization has been deleted.');
        } catch (ForeignKeyConstraintViolationException  $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('organization_index');
    }
}
