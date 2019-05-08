<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\User;
use AppBundle\Form\Data\Settings\OutageData;
use AppBundle\Form\SettingOutageType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/outage")
 * @PermissionControllerName(SettingController::class)
 */
class SettingOutageController extends BaseController
{
    /**
     * @Route("", name="setting_outage_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Outage", path="System -> Settings -> Outage", formTypes={SettingOutageType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var OutageData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(OutageData::class);

        if ($options->notificationPingUser !== null) {
            $options->notificationPingUser = $this->em->getRepository(User::class)->findOneBy(
                [
                    'id' => $options->notificationPingUser,
                    'deletedAt' => null,
                ]
            );
        }

        $form = $this->createForm(SettingOutageType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $options->notificationPingUser = $options->notificationPingUser
                ? $options->notificationPingUser->getId()
                : null;

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_outage_edit');
        }

        return $this->render(
            'setting/outage/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
