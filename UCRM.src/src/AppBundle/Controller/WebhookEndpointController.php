<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\WebhookAddress;
use AppBundle\Facade\WebhookFacade;
use AppBundle\Form\WebhookAddressType;
use AppBundle\Grid\Webhook\EndpointGridFactory;
use AppBundle\Grid\Webhook\EventGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/webhooks/endpoints")
 */
class WebhookEndpointController extends BaseController
{
    /**
     * @Route("", name="webhook_endpoint_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Endpoints", path="System -> Webhooks -> Endpoints")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(EndpointGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'webhook/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="webhook_endpoint_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function webhookNewAction(Request $request): Response
    {
        $webhookAddress = new WebhookAddress();
        $requestUrl = $request->query->get('url');
        if (is_string($requestUrl)) {
            $webhookAddress->setUrl($requestUrl);
        }
        if ($request->query->getBoolean('insecure')) {
            $webhookAddress->setVerifySslCertificate(false);
        }
        $form = $this->createForm(WebhookAddressType::class, $webhookAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('webhook_endpoint_index');
            }

            $this->get(WebhookFacade::class)->handleCreate($webhookAddress);

            $this->addTranslatedFlash('success', 'Webhook has been created.');

            return $this->redirectToRoute('webhook_endpoint_index');
        }

        return $this->render(
            'webhook/edit.html.twig',
            [
                'webhook' => $webhookAddress,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/test", name="webhook_endpoint_test", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function webhookTestAction(WebhookAddress $webhookAddress): Response
    {
        $this->notDeleted($webhookAddress);

        $this->get(WebhookFacade::class)->handleTestSend($webhookAddress);

        $this->addTranslatedFlash('success', 'Webhook request has been sent.');

        return $this->redirectToRoute(
            'webhook_endpoint_show',
            [
                'id' => $webhookAddress->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="webhook_endpoint_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function webhookEditAction(Request $request, WebhookAddress $webhookAddress): Response
    {
        $this->notDeleted($webhookAddress);

        $form = $this->createForm(WebhookAddressType::class, $webhookAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('webhook_endpoint_index');
            }

            $this->get(WebhookFacade::class)->handleUpdate($webhookAddress);

            $this->addTranslatedFlash('success', 'Webhook has been saved.');

            return $this->redirectToRoute(
                'webhook_endpoint_show',
                [
                    'id' => $webhookAddress->getId(),
                ]
            );
        }

        return $this->render(
            'webhook/edit.html.twig',
            [
                'webhook' => $webhookAddress,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="webhook_endpoint_delete", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function webhookDeleteAction(WebhookAddress $webhookAddress): Response
    {
        $this->notDeleted($webhookAddress);

        $this->get(WebhookFacade::class)->handleDelete($webhookAddress);

        $this->addTranslatedFlash('success', 'Webhook has been deleted.');

        return $this->redirectToRoute('webhook_endpoint_index');
    }

    /**
     * @Route("/{id}", name="webhook_endpoint_show", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function webhookShowAction(Request $request, WebhookAddress $webhookAddress): Response
    {
        $grid = $this->get(EventGridFactory::class)->create($webhookAddress);

        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $this->notDeleted($webhookAddress);

        return $this->render(
            'webhook/show_endpoint.html.twig',
            [
                'webhookAddress' => $webhookAddress,
                'grid' => $grid,
            ]
        );
    }
}
