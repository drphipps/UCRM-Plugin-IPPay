<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Import\Exception\ImportException;
use AppBundle\Component\Import\Facade\ClientImportSaveFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;
use AppBundle\Entity\Import\ImportInterface;
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
class ClientImportSaveController extends BaseController
{
    /**
     * @var ClientImportSaveFacade
     */
    private $clientImportSaveFacade;

    public function __construct(ClientImportSaveFacade $clientImportSaveFacade)
    {
        $this->clientImportSaveFacade = $clientImportSaveFacade;
    }

    /**
     * @Route("/save/{id}", name="import_clients_save", requirements={"id": "%uuid_regex%"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function saveClientsAction(ClientImport $clientImport): Response
    {
        if (! $clientImport->isStatusDone(ImportInterface::STATUS_ITEMS_VALIDATED)) {
            $this->addTranslatedFlash(
                'error',
                'The import must be validated before it can be started.'
            );

            return $this->redirectToRoute(
                'import_clients_preview',
                [
                    'id' => $clientImport->getId(),
                ]
            );
        }

        try {
            $this->clientImportSaveFacade->enqueueSave($clientImport);
        } catch (ImportException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());

            return $this->redirectToRoute(
                'import_clients_preview',
                [
                    'id' => $clientImport->getId(),
                ]
            );
        }

        $count = $this->em->getRepository(ClientImportItem::class)->getCountForImportStart($clientImport);
        $this->addTranslatedFlash(
            'success',
            '%count% clients will be imported in the background within a few minutes.',
            $count,
            [
                '%count%' => $count,
            ]
        );

        return $this->redirectToRoute('import_clients_index');
    }
}
