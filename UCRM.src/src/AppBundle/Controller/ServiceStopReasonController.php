<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Form\ServiceStopReasonType;
use AppBundle\Grid\ServiceStopReason\ServiceStopReasonGridFactory;
use AppBundle\Security\Permission;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/other/service-stop-reason")
 */
class ServiceStopReasonController extends BaseController
{
    /**
     * @Route("", name="service_stop_reason_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Reasons for stop service", path="System -> Other -> Reasons for stop service")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(ServiceStopReasonGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'service_stop_reason/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="service_stop_reason_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $reason = new ServiceStopReason();
        $form = $this->createForm(ServiceStopReasonType::class, $reason);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($reason);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Reason has been created.');

            return $this->redirectToRoute(
                'service_stop_reason_show',
                [
                    'id' => $reason->getId(),
                ]
            );
        }

        return $this->render(
            'service_stop_reason/new.html.twig',
            [
                'reason' => $reason,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}", name="service_stop_reason_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     */
    public function showAction(ServiceStopReason $reason): Response
    {
        return $this->render(
            'service_stop_reason/show.html.twig',
            [
                'reason' => $reason,
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="service_stop_reason_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, ServiceStopReason $reason): Response
    {
        $form = $this->createForm(ServiceStopReasonType::class, $reason);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($reason);
            $this->em->flush();

            $this->addTranslatedFlash('success', 'Reason has been saved.');

            return $this->redirectToRoute('service_stop_reason_show', ['id' => $reason->getId()]);
        }

        return $this->render(
            'service_stop_reason/edit.html.twig',
            [
                'reason' => $reason,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="service_stop_reason_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(ServiceStopReason $reason): Response
    {
        if ($reason->getId() < ServiceStopReason::REASON_MIN_CUSTOM_ID) {
            $this->addTranslatedFlash('error', 'This reason cannot be deleted.');
        } else {
            try {
                $this->em->remove($reason);
                $this->em->flush();

                $this->addTranslatedFlash('success', 'Reason has been deleted.');
            } catch (ForeignKeyConstraintViolationException  $e) {
                $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
            }
        }

        return $this->redirectToRoute('service_stop_reason_index');
    }
}
