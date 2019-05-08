<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\WebhookEventMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\WebhookEndpointController;
use AppBundle\Entity\WebhookEvent;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(WebhookEndpointController::class)
 */
class WebhookEventController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var WebhookEventMapper
     */
    private $mapper;

    public function __construct(WebhookEventMapper $webhookEventMapper)
    {
        $this->mapper = $webhookEventMapper;
    }

    /**
     * @Get("/webhook-events/{uuid}", name="webhook_event_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(WebhookEvent $webhookEvent): View
    {
        return $this->view(
            $this->mapper->reflect($webhookEvent)
        );
    }
}
