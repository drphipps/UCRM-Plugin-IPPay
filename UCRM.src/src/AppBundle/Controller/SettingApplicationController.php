<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Entity\General;
use AppBundle\Event\Option\UrlConfigurationChangedEvent;
use AppBundle\Exception\SequenceException;
use AppBundle\Facade\CertificateFacade;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\OptionsFacade;
use AppBundle\FileManager\SuspensionFileManager;
use AppBundle\Form\Data\Settings\ApplicationData;
use AppBundle\Form\SettingApplicationType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Options;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/application")
 * @PermissionControllerName(SettingController::class)
 */
class SettingApplicationController extends BaseController
{
    /**
     * @Route("", name="setting_application_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Application",
     *     path="System -> Settings -> Application",
     *     formTypes={SettingApplicationType::class},
     *     extra={
     *         "Server configuration",
     *         "General configuration",
     *         "PDF options"
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var ApplicationData $applicationData */
        $applicationData = $optionsManager->loadOptionsIntoDataClass(ApplicationData::class);
        $oldOptions = clone $applicationData;

        $form = $this->createForm(SettingApplicationType::class, $applicationData);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (
                $oldOptions->serverIp !== $applicationData->serverIp
                || $oldOptions->serverSuspendPort !== $applicationData->serverSuspendPort
            ) {
                $synchronizationManager = $this->container->get(SynchronizationManager::class);
                if ($synchronizationManager->unsynchronizeAllDevices()) {
                    $this->em->flush();
                }
            }

            if ($oldOptions->serverPort !== $applicationData->serverPort) {
                $this->addTranslatedFlash(
                    'warning',
                    'Server port changed, make sure this port number matches the port used by docker and port used in your Firewall/NAT rules used on your routers. Otherwise, UCRM may be not accessible.'
                );
            }

            if ($oldOptions->serverSuspendPort !== $applicationData->serverSuspendPort) {
                $this->addTranslatedFlash(
                    'warning',
                    'Server suspend port changed, make sure this port number matches the port used by docker and port used in your Firewall/NAT rules used on your routers. Otherwise, UCRM may be not accessible.'
                );
            }

            $optionsManager->updateOptions($applicationData);

            if (
                $oldOptions->serverPort !== $applicationData->serverPort
                || $oldOptions->serverIp !== $applicationData->serverIp
                || $oldOptions->serverFqdn !== $applicationData->serverFqdn
            ) {
                $this->get(EventDispatcherInterface::class)->dispatch(
                    UrlConfigurationChangedEvent::class,
                    new UrlConfigurationChangedEvent()
                );

                $this->get(CertificateFacade::class)->runServerControl();
            }

            $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_SYSTEM, '1');

            $this->container->get(SuspensionFileManager::class)->regenerateSuspensionFile();

            if ($applicationData->clientIdNext) {
                try {
                    $this->container->get(ClientFacade::class)->setNextClientId($applicationData->clientIdNext);
                } catch (SequenceException $e) {
                    $this->addTranslatedFlash(
                        'warning',
                        'New value for next client ID is lower or equal than max client ID.'
                    );
                }
            }

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_application_edit');
        }

        return $this->render(
            'setting/application/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
