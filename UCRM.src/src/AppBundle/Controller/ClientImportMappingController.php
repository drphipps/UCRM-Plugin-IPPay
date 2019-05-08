<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Import\DataProvider\ImportDataProvider;
use AppBundle\Component\Import\Facade\ImportFacade;
use AppBundle\Component\Import\FileManager\ImportFileManager;
use AppBundle\Entity\CsvImportMapping;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\Organization;
use AppBundle\Form\CsvImportClientType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/import/clients")
 * @PermissionControllerName(ClientImportController::class)
 */
class ClientImportMappingController extends BaseController
{
    /**
     * @var ImportFacade
     */
    private $importFacade;

    /**
     * @var ImportFileManager
     */
    private $importFileManager;

    /**
     * @var ImportDataProvider
     */
    private $importDataProvider;

    public function __construct(
        ImportFacade $importFacade,
        ImportFileManager $importFileManager,
        ImportDataProvider $importDataProvider
    ) {
        $this->importFacade = $importFacade;
        $this->importFileManager = $importFileManager;
        $this->importDataProvider = $importDataProvider;
    }

    /**
     * @Route("/map/{id}", name="import_clients_map", requirements={"id": "%uuid_regex%"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function mappingAction(Request $request, ClientImport $clientImport): Response
    {
        if ($clientImport->isStatusDone(ImportInterface::STATUS_ENQUEUED)) {
            $this->addTranslatedFlash(
                'error',
                'The import process is already started, this action is no longer possible.'
            );

            return $this->redirectToRoute('import_clients_index');
        }

        if (! $this->importFileManager->exists($clientImport)) {
            $this->importFacade->handleDelete($clientImport);
            $this->addTranslatedFlash('error', 'There is nothing to import.');

            return $this->redirectToRoute('import_clients_index');
        }

        if (! $this->importFileManager->checkEncodingUTF8($clientImport)) {
            $this->addTranslatedFlash(
                'warning',
                'CSV file is not in valid UTF-8, special characters may not be imported correctly.'
            );
        }

        $fields = $this->importDataProvider->getCsvFields($clientImport);
        $csvImportMapping = $this->em->getRepository(CsvImportMapping::class)->findOneBy(
            [
                'hash' => $clientImport->getCsvHash(),
                'type' => CsvImportMapping::TYPE_CLIENT,
            ]
        );

        $csvMappingData = $this->importDataProvider->getDefaultCsvMappingData(
            $clientImport,
            $fields,
            $csvImportMapping
        );
        $form = $this->createForm(
            CsvImportClientType::class,
            $csvMappingData,
            [
                'mapping_choices' => $fields,
                'include_organization_select' => $this->em->getRepository(Organization::class)->getCount() !== 1,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clientImportBeforeUpdate = clone $clientImport;
            $clientImport->setOrganization($csvMappingData->organization);
            $clientImport->setCsvMapping($csvMappingData->mapping);
            $clientImport->setStatus(ImportInterface::STATUS_MAPPED);

            if (! $csvImportMapping) {
                $csvImportMapping = new CsvImportMapping();
                $csvImportMapping->setHash($clientImport->getCsvHash());
                $csvImportMapping->setType(CsvImportMapping::TYPE_CLIENT);
            }
            $csvImportMapping->setMapping($csvMappingData->mapping);

            $this->importFacade->handleUpdateWithMapping($clientImport, $clientImportBeforeUpdate, $csvImportMapping);

            return $this->redirectToRoute(
                'import_clients_preview',
                [
                    'id' => $clientImport->getId(),
                ]
            );
        }

        return $this->render(
            'import_clients/map.html.twig',
            [
                'form' => $form->createView(),
                'type' => 'clients',
                'import' => $clientImport,
            ]
        );
    }
}
