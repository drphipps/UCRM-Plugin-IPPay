<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Organization;
use AppBundle\Facade\FccFacade;
use AppBundle\Form\Data\FccReportData;
use AppBundle\Form\Data\Settings\FccData;
use AppBundle\Form\FccReportType;
use AppBundle\Form\SettingFccType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/tools/fcc-reports")
 * @PermissionControllerName(SettingController::class)
 */
class FccReportsController extends BaseController
{
    /**
     * @Route("", name="fcc_reports_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="FCC reports", path="System -> Tools -> FCC reports")
     */
    public function indexAction(Request $request): Response
    {
        $organizationRepository = $this->em->getRepository(Organization::class);
        $organizationCount = $organizationRepository->getCount();
        $organization = $organizationCount === 1 ? $organizationRepository->getSelectedOrAlone() : null;

        $fccReportDeployment = new FccReportData();
        if ($organization) {
            $fccReportDeployment->organizations->add($organization);
        }
        $fccReportDeploymentForm = $this->container->get('form.factory')
            ->createNamedBuilder(
                'fccReportDeploymentForm',
                FccReportType::class,
                $fccReportDeployment
            )
            ->getForm();

        $fccReportSubscription = new FccReportData();
        if ($organization) {
            $fccReportSubscription->organizations->add($organization);
        }
        $fccReportSubscriptionForm = $this->container->get('form.factory')
            ->createNamedBuilder(
                'fccReportSubscriptionForm',
                FccReportType::class,
                $fccReportSubscription
            )
            ->getForm();

        /** @var FccData $options */
        $options = $this->get(OptionsManager::class)->loadOptionsIntoDataClass(FccData::class);
        $optionsForm = $this->createForm(SettingFccType::class, $options);
        $optionsForm->handleRequest($request);
        if ($optionsForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($optionsForm->isSubmitted() && $optionsForm->isValid()) {
            $this->get(OptionsManager::class)->updateOptions($options);

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('fcc_reports_index');
        }

        $fccReportDeploymentForm->handleRequest($request);
        $fccReportSubscriptionForm->handleRequest($request);
        if ($fccReportDeploymentForm->isSubmitted() || $fccReportSubscriptionForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($fccReportDeploymentForm->isSubmitted() && $fccReportDeploymentForm->isValid()) {
            $this->get(FccFacade::class)->prepareDeploymentDownload(
                $this->trans('Fixed Broadband Deployment report'),
                $fccReportDeployment->organizations->toArray(),
                $this->getUser()
            );

            $this->addTranslatedFlash(
                'success',
                'Report was added to queue. You can download it in System > Tools > Downloads.',
                null,
                [
                    '%link%' => $this->generateUrl('download_index'),
                ]
            );

            return $this->redirectToRoute('fcc_reports_index');
        }

        if ($fccReportSubscriptionForm->isSubmitted() && $fccReportSubscriptionForm->isValid()) {
            $this->get(FccFacade::class)->prepareSubscriptionDownload(
                $this->trans('Fixed Broadband Subscription report'),
                $fccReportSubscription->organizations->toArray(),
                $this->getUser()
            );

            $this->addTranslatedFlash(
                'success',
                'Report was added to queue. You can download it in System > Tools > Downloads.',
                null,
                [
                    '%link%' => $this->generateUrl('download_index'),
                ]
            );

            return $this->redirectToRoute('fcc_reports_index');
        }

        return $this->render(
            'fcc_reports/index.html.twig',
            [
                'optionsForm' => $optionsForm->createView(),
                'fccReportDeploymentForm' => $fccReportDeploymentForm->createView(),
                'fccReportSubscriptionForm' => $fccReportSubscriptionForm->createView(),
            ]
        );
    }
}
