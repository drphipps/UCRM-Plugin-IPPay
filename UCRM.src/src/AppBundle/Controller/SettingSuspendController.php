<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Service;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Form\Data\Settings\SuspendData;
use AppBundle\Form\SettingSuspendType;
use AppBundle\Security\Permission;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/suspend")
 */
class SettingSuspendController extends BaseController
{
    /**
     * @Route("", name="setting_suspend_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Suspend", path="System -> Billing -> Suspend", formTypes={SettingSuspendType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var SuspendData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(SuspendData::class);
        $oldOptions = clone $options;

        $form = $this->createForm(SettingSuspendType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($oldOptions->suspendEnabled && ! $options->suspendEnabled) {
                $serviceFacade = $this->get(ServiceFacade::class);
                $suspendedServices = $this->em->getRepository(Service::class)->getSuspendedServices();
                $serviceFacade->setSuspendDisabled($suspendedServices);
            }

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_suspend_edit');
        }

        return $this->render(
            'setting/suspend/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
