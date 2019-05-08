<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Entity\Device;
use AppBundle\Form\Data\Settings\QosData;
use AppBundle\Form\SettingQosType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/qos")
 * @PermissionControllerName(SettingController::class)
 */
class SettingQosController extends BaseController
{
    /**
     * @Route("", name="setting_qos_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="QoS", path="System -> Settings -> QoS", formTypes={SettingQosType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var QosData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(QosData::class);

        $form = $this->createForm(SettingQosType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(QoSSynchronizationManager::class)->unsynchronizeSettings(
                $options->qosEnabled,
                $options->qosDestination,
                $options->qosInterfaceAirOs
            );

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_qos_edit');
        }

        $gatewayRouters = $this->em->getRepository(Device::class)->findBy(
            [
                'isGateway' => true,
            ]
        );

        $gatewayRouters = array_map(
            function (Device $device) {
                return $device->getNameWithSite();
            },
            $gatewayRouters
        );

        return $this->render(
            'setting/qos/edit.html.twig',
            [
                'form' => $form->createView(),
                'gatewayRouters' => $gatewayRouters,
            ]
        );
    }
}
