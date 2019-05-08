<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\EntityLog;
use AppBundle\Facade\ClientExportFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\ActionLogger;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client")
 * @PermissionControllerName(ClientController::class)
 */
class ClientExportController extends BaseController
{
    /**
     * @Route("/{id}/export", name="client_export", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function exportAction(Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::CLIENT_EXPORT);

        return $this->render(
            'client/components/view/export_modal.html.twig',
            [
                'client' => $client,
            ]
        );
    }

    /**
     * @Route("/{id}/do-export", name="client_export_do", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function doExportAction(Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::CLIENT_EXPORT);

        $message['logMsg'] = [
            'message' => 'Export for client %s was downloaded.',
            'replacements' => sprintf('%s (ID: %d)', $client->getNameForView(), $client->getId()),
        ];

        $this->get(ActionLogger::class)->log(
            $message,
            $this->getUser(),
            $client,
            EntityLog::CLIENT_EXPORT_DOWNLOAD
        );

        return $this->get(ClientExportFacade::class)->handleClientExport($client);
    }
}
