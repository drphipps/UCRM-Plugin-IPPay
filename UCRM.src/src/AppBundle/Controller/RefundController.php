<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Refund;
use AppBundle\Facade\RefundFacade;
use AppBundle\Form\RefundType;
use AppBundle\Grid\Refund\RefundGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing/refunds")
 */
class RefundController extends BaseController
{
    /**
     * @Route("", name="refund_index")
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(RefundGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }

        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'refunds/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="refund_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newRefundAction(Request $request): Response
    {
        $refund = new Refund();
        $refund->setCreatedDate(new \DateTime());

        $options = [
            'action' => $this->generateUrl('refund_new'),
        ];
        $form = $this->createForm(RefundType::class, $refund, $options);
        $form->handleRequest($request);

        if ($refund->getClient()) {
            $refund->setCurrency($refund->getClient()->getOrganization()->getCurrency());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(RefundFacade::class)->handleCreate($refund);

            $this->addTranslatedFlash('success', 'Refund has been created.');

            return $this->createAjaxRedirectResponse(
                'refund_show',
                [
                    'id' => $refund->getId(),
                ]
            );
        }

        return $this->render(
            'refunds/components/add_form.html.twig',
            [
                'refund' => $refund,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="refund_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showRefundAction(Refund $refund): Response
    {
        return $this->render(
            'refunds/show.html.twig',
            [
                'refund' => $refund,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="refund_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Refund $refund): Response
    {
        if (! $this->get(RefundFacade::class)->handleDelete($refund)) {
            $this->addTranslatedFlash('error', 'Refund could not be deleted.');

            return $this->redirectToRoute(
                'refund_show',
                [
                    'id' => $refund->getId(),
                ]
            );
        }

        $this->addTranslatedFlash('success', 'Refund has been deleted.');

        return $this->redirectToRoute('refund_index');
    }

    /**
     * @Route("/client-json/{id}", name="refund_get_client_json", options={"expose"=true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function getClientJsonAction(Client $client): Response
    {
        $currency = $client->getOrganization()->getCurrency();

        $credit = $this->container->get(Formatter::class)->formatCurrency(
            $client->getAccountStandingsRefundableCredit(),
            $currency->getCode(),
            $client->getOrganization()->getLocale()
        );

        return new JsonResponse(
            [
                'currency' => sprintf('%s (%s)', $currency->getCode(), $currency->getSymbol()),
                'credit' => $credit,
            ]
        );
    }
}
