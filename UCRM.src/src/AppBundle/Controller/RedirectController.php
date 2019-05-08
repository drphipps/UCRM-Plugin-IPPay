<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/redirect")
 */
class RedirectController extends BaseController
{
    /**
     * @Route("/client/{id}/to/unique-service/", name="redirect_client_to_unique_service", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function toUniqueServiceAction(Client $client): Response
    {
        if ($client->getNotDeletedServices()->count() === 1) {
            $route = 'client_service_show';
            /** @var Service $service */
            $service = $client->getNotDeletedServices()->first();
            $id = $service->getId();
        } else {
            $route = 'client_show';
            $id = $client->getId();
        }

        return $this->redirect(
            $this->generateUrl(
                $route,
                [
                    'id' => $id,
                ]
            )
        );
    }
}
