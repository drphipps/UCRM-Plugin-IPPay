<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Form\Data\Settings\SyncData;
use AppBundle\Form\SettingSyncType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/sync")
 * @PermissionControllerName(SettingController::class)
 */
class SettingSyncController extends BaseController
{
    /**
     * @Route("", name="setting_sync_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Sync", path="System -> Settings -> Sync", formTypes={SettingSyncType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var SyncData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(SyncData::class);

        $form = $this->createForm(SettingSyncType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_sync_edit');
        }

        return $this->render(
            'setting/sync/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
