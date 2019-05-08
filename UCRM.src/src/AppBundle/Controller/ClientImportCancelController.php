<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Import\Facade\ImportFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/import/clients")
 * @PermissionControllerName(ClientImportController::class)
 */
class ClientImportCancelController extends BaseController
{
    /**
     * @var ImportFacade
     */
    private $importFacade;

    public function __construct(ImportFacade $importFacade)
    {
        $this->importFacade = $importFacade;
    }

    /**
     * @Route("/cancel/{id}", name="import_clients_cancel", requirements={"id": "%uuid_regex%"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function cancelClientsAction(ClientImport $clientImport): Response
    {
        $this->importFacade->handleDelete($clientImport);

        return $this->redirectToRoute('import_clients_index');
    }
}
