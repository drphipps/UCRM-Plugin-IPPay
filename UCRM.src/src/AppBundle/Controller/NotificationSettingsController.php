<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Form\Data\Settings\NotificationsData;
use AppBundle\Form\SettingNotificationType;
use AppBundle\Security\Permission;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/customization/notification-settings")
 */
class NotificationSettingsController extends BaseController
{
    /**
     * @Route("", name="notification_settings_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Notification settings",
     *     path="System -> Customization -> Notification settings",
     *     formTypes={SettingNotificationType::class},
     *     extra={"System notifications", "Client notifications"}
     * )
     */
    public function settingsAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        $options = $optionsManager->loadOptionsIntoDataClass(NotificationsData::class);
        $oldOptions = clone $options;

        $form = $this->createForm(SettingNotificationType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->transactional(
                function () use ($oldOptions, $options, $optionsManager) {
                    if (! $options->notificationInvoiceNearDue) {
                        $options->notificationInvoiceNearDueDays = $oldOptions->notificationInvoiceNearDueDays;
                    }

                    $optionsManager->updateOptions($options);
                }
            );

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('notification_settings_index');
        }

        return $this->render(
            'notification/settings.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
