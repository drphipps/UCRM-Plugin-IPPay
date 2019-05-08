<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Component\Import\Facade\ImportFacade;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Organization;
use AppBundle\Form\CsvUploadType;
use AppBundle\Form\Data\CsvUploadData;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/import/clients")
 * @PermissionControllerName(ClientImportController::class)
 */
class ClientImportUploadController extends BaseController
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
     * @Route("", name="import_clients_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Clients import", path="System -> Tools -> Clients import")
     */
    public function indexClientAction(Request $request): Response
    {
        $csvUploadData = new CsvUploadData();
        $form = $this->createForm(
            CsvUploadType::class,
            $csvUploadData,
            [
                // needed because of Dropzone.js
                'action' => $this->generateUrl('import_clients_index'),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (! $csvUploadData->file) {
                $form->get('file')->addError(new FormError('Uploaded file is not valid.'));
            }

            if ($form->isValid()) {
                $clientImport = new ClientImport();
                $this->importFacade->handleCreate(
                    $clientImport,
                    $csvUploadData,
                    $this->getUser()
                );

                if ($request->isXmlHttpRequest()) {
                    return $this->createAjaxRedirectResponse(
                        'import_clients_map',
                        [
                            'id' => $clientImport->getId(),
                        ]
                    );
                }

                return $this->redirectToRoute(
                    'import_clients_map',
                    [
                        'id' => $clientImport->getId(),
                    ]
                );
            }
        }

        if ($request->isXmlHttpRequest()) {
            $this->invalidateTemplate(
                'import-clients-form-container',
                'import_clients/components/form.html.twig',
                [
                    'form' => $form->createView(),
                    'sampleCsvFile' => $this->getSampleCsvFile(),
                ]
            );

            return $this->createAjaxResponse();
        }

        return $this->render(
            'import_clients/index.html.twig',
            [
                'form' => $form->createView(),
                'sampleCsvFile' => $this->getSampleCsvFile(),
            ]
        );
    }

    private function getSampleCsvFile(): string
    {
        if (
            ($organization = $this->em->getRepository(Organization::class)->getFirstSelected())
            && $organization->getCountry()
            && $organization->getCountry()->isFccCountry()
        ) {
            return 'sample_csv/clients_us.csv';
        }

        return 'sample_csv/clients_europe.csv';
    }
}
