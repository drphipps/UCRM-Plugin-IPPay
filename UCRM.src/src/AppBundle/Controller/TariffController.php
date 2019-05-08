<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\TariffPeriodDataProvider;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Tariff;
use AppBundle\Facade\TariffFacade;
use AppBundle\Form\TariffType;
use AppBundle\Grid\Tariff\TariffGridFactory;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/items/service-plans")
 */
class TariffController extends BaseController
{
    /**
     * @Route("", name="tariff_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Service plans", path="System -> Service plans & Products -> Service plans")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(TariffGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'tariff/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="tariff_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $tariff = new Tariff();
        $tariffFacade = $this->get(TariffFacade::class);
        $tariffFacade->setDefaults($tariff);
        if (! $tariff->getName() && $newName = $request->get('name')) {
            $tariff->setName($newName);
        }

        $form = $this->createForm(
            TariffType::class,
            $tariff,
            [
                'include_organization_select' => $this->em->getRepository(Organization::class)->getCount() > 1,
                'include_period_enabled' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($tariff->getPeriods() as $period) {
                $period->setEnabled($period->hasPrice());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $tariffFacade->handleCreate($tariff);

            $this->addTranslatedFlash('success', 'Service plan has been created.');

            return $this->redirectToRoute(
                'tariff_show',
                [
                    'id' => $tariff->getId(),
                ]
            );
        }

        return $this->render(
            'tariff/new.html.twig',
            [
                'form' => $form->createView(),
                'tariff' => $tariff,
            ]
        );
    }

    /**
     * @Route("/new-modal/{id}", name="tariff_new_modal", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newModalAction(Request $request, Organization $organization): Response
    {
        $tariff = new Tariff();
        $tariffFacade = $this->get(TariffFacade::class);
        $tariffFacade->setDefaults($tariff);
        $tariff->setOrganization($organization);
        if (! $tariff->getName() && $newName = $request->get('name')) {
            $tariff->setName($newName);
        }

        $url = $this->generateUrl(
            'tariff_new_modal',
            [
                'id' => $organization->getId(),
            ]
        );
        $form = $this->createForm(
            TariffType::class,
            $tariff,
            [
                'action' => $url,
                'include_organization_select' => false,
                'include_period_enabled' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $hasEnabledPeriod = false;
            foreach ($tariff->getPeriods() as $period) {
                $period->setEnabled($period->hasPrice());
                $hasEnabledPeriod = $hasEnabledPeriod || $period->isEnabled();
            }

            if (! $hasEnabledPeriod) {
                $form->get('periods')->addError(new FormError('Set up at least one billing period.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $tariffFacade->handleCreate($tariff);

            $this->addTranslatedFlash('success', 'Service plan has been created.');

            return $this->createAjaxResponse(
                [
                    'data' => [
                        'value' => $tariff->getId(),
                        'label' => $tariff->getName(),
                        'attr' => [
                            'data-taxable' => (int) $tariff->getTaxable(),
                            'data-tax' => $tariff->getTax() ? $tariff->getTax()->getId() : null,
                            'data-invoice-label' => $tariff->getInvoiceLabelOrName(),
                            'data-minimum-contract-length' => $tariff->getMinimumContractLengthMonths(),
                            'data-setup-fee' => $tariff->getSetupFee(),
                            'data-early-termination-fee' => $tariff->getEarlyTerminationFee(),
                        ],
                    ],
                ]
            );
        }

        return $this->render(
            'tariff/new_modal.html.twig',
            [
                'form' => $form->createView(),
                'tariff' => $tariff,
                'modal' => true,
            ]
        );
    }

    /**
     * @Route("/{id}", name="tariff_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Tariff $tariff): Response
    {
        $this->notDeleted($tariff);

        return $this->render(
            'tariff/show.html.twig',
            [
                'tariff' => $tariff,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="tariff_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Tariff $tariff): Response
    {
        $this->notDeleted($tariff);

        $oldTariff = clone $tariff;
        $tariffFacade = $this->get(TariffFacade::class);

        // we must get period prices before the form is handled, because the values would be overwritten there
        // this is to correctly handle period price change warning when there is a validation error somewhere
        $periodPrices = [];
        foreach ($tariff->getPeriods() as $period) {
            $periodPrices[$period->getId()] = $period->getPrice();
        }

        $form = $this->createForm(TariffType::class, $tariff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tariffFacade->handleUpdate($tariff, $oldTariff);

            $this->addTranslatedFlash('success', 'Service plan has been saved.');

            return $this->redirectToRoute(
                'tariff_show',
                [
                    'id' => $tariff->getId(),
                ]
            );
        }

        $clientCounts = $this->get(TariffPeriodDataProvider::class)->getActiveClientCountByPeriod($tariff);

        return $this->render(
            'tariff/edit.html.twig',
            [
                'tariff' => $tariff,
                'form' => $form->createView(),
                'clientCounts' => $this->get(TariffPeriodDataProvider::class)->getActiveClientCountByPeriod($tariff),
                'periodPrices' => $periodPrices,
                'showChangesTooltip' => (bool) $clientCounts,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="tariff_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Tariff $tariff): RedirectResponse
    {
        $this->notDeleted($tariff);

        if (! $this->get(TariffFacade::class)->handleDelete($tariff)) {
            $this->addTranslatedFlash('error', 'Service plan could not be deleted.');

            return $this->redirectToRoute(
                'tariff_show',
                [
                    'id' => $tariff->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Service plan has been deleted.');

        return $this->redirectToRoute('tariff_index');
    }
}
