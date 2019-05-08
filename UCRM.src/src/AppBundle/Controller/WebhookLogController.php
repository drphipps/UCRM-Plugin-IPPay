<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Facade\WebhookFacade;
use AppBundle\Grid\Webhook\EventGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/webhooks/log")
 * @PermissionControllerName(WebhookEndpointController::class)
 */
class WebhookLogController extends BaseController
{
    /**
     * @Route("", name="webhook_log_index")
     * @Method({"GET"})
     * @Permission("view")
     * @Searchable(heading="Request log", path="System -> Webhooks -> Request log")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(EventGridFactory::class)->create();

        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'webhook/log.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="webhook_log_show", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function showAction(WebhookEvent $webhookEvent): Response
    {
        $this->notDeleted($webhookEvent);

        return $this->render(
            'webhook/show.html.twig',
            [
                'webhookEvent' => $webhookEvent,
            ]
        );
    }

    /**
     * @Route("/{id}/resend", name="webhook_log_resend", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function resendAction(WebhookEvent $webhookEvent): Response
    {
        $this->notDeleted($webhookEvent);

        $this->get(WebhookFacade::class)->handleResend($webhookEvent);

        $this->addTranslatedFlash('success', 'Webhook notification has been resent.');

        return $this->redirectToRoute(
            'webhook_log_index'
        );
    }
}
