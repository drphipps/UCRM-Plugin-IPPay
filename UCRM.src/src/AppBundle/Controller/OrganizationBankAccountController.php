<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\OrganizationBankAccount;
use AppBundle\Facade\OrganizationBankAccountFacade;
use AppBundle\Form\OrganizationBankAccountType;
use AppBundle\Grid\OrganizationBankAccount\OrganizationBankAccountGridFactory;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/organizations-bank-accounts")
 */
class OrganizationBankAccountController extends BaseController
{
    /**
     * @Route("", name="organization_bank_account_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Bank accounts", path="System -> Billing -> Bank accounts")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(OrganizationBankAccountGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'organization_bank_account/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="organization_bank_account_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $organizationBankAccount = new OrganizationBankAccount();
        $form = $this->createForm(OrganizationBankAccountType::class, $organizationBankAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($organizationBankAccount);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Bank account has been created.');

            return $this->redirectToRoute(
                'organization_bank_account_show',
                [
                    'id' => $organizationBankAccount->getId(),
                ]
            );
        }

        return $this->render(
            'organization_bank_account/new.html.twig',
            [
                'organization_bank_account' => $organizationBankAccount,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="organization_bank_account_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(OrganizationBankAccount $organizationBankAccount): Response
    {
        return $this->render(
            'organization_bank_account/show.html.twig',
            [
                'organization_bank_account' => $organizationBankAccount,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="organization_bank_account_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, OrganizationBankAccount $organizationBankAccount): Response
    {
        $form = $this->createForm(OrganizationBankAccountType::class, $organizationBankAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($organizationBankAccount);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Bank account has been saved.');

            return $this->redirectToRoute(
                'organization_bank_account_show',
                [
                    'id' => $organizationBankAccount->getId(),
                ]
            );
        }

        return $this->render(
            'organization_bank_account/edit.html.twig',
            [
                'organization_bank_account' => $organizationBankAccount,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="organization_bank_account_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(OrganizationBankAccount $organizationBankAccount): Response
    {
        try {
            $this->em->remove($organizationBankAccount);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Bank account has been deleted.');
        } catch (ForeignKeyConstraintViolationException  $e) {
            $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
        }

        return $this->redirectToRoute('organization_bank_account_index');
    }

    /**
     * @Route("/new-modal", name="bank_account_new_modal")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request): Response
    {
        $organizationBankAccount = new OrganizationBankAccount();
        $url = $this->generateUrl('bank_account_new_modal');
        $form = $this->createForm(OrganizationBankAccountType::class, $organizationBankAccount, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(OrganizationBankAccountFacade::class)->handleOrganizationBankAccountSave(
                $organizationBankAccount
            );

            $this->addTranslatedFlash('success', 'Bank account has been created.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $organizationBankAccount->getId(),
                        'label' => $organizationBankAccount->accountLabel(),
                    ],
                ]
            );
        }

        return $this->render(
            'organization_bank_account/new_modal.html.twig',
            [
                'organization_bank_account' => $organizationBankAccount,
                'form' => $form->createView(),
            ]
        );
    }
}
