<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Command\Maintenance;
use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Form\Data\Settings\ApplicationData;
use AppBundle\Form\Data\Settings\LogsData;
use AppBundle\Form\SettingLogsType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/logs")
 * @PermissionControllerName(SettingController::class)
 */
class SettingLogsController extends BaseController
{
    /**
     * @Route("", name="setting_logs_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="Logs", path="System -> Settings -> Logs", formTypes={SettingLogsType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var ApplicationData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(LogsData::class);

        $form = $this->createForm(SettingLogsType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            /** @var SubmitButton $saveAndPurgeButton */
            $saveAndPurgeButton = $form->get('saveAndPurge');
            if ($saveAndPurgeButton->isClicked()) {
                $deleted = $this->get(Maintenance::class)->runLogsCleanup();
                $this->addTranslatedFlash(
                    'info',
                    'Deleted %count% log entries.',
                    $deleted,
                    [
                        '%count%' => $deleted,
                    ]
                );
            }

            return $this->redirectToRoute('setting_logs_edit');
        }

        return $this->render(
            'setting/logs/edit.html.twig',
            [
                'form' => $form->createView(),
                'entityLogCount' => $this->em->getRepository(EntityLog::class)->getApproximateCount(),
                'emailLogCount' => $this->em->getRepository(EmailLog::class)->getApproximateCount(),
                'deviceLogCount' => $this->em->getRepository(DeviceLog::class)->getApproximateCount(),
                'serviceDeviceLogCount' => $this->em->getRepository(ServiceDeviceLog::class)->getApproximateCount(),
                'headerNotificationCount' => $this->em->getRepository(HeaderNotification::class)->getApproximateCount(),
            ]
        );
    }
}
