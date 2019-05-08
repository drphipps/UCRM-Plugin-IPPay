<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Form\Data\Settings\ClientZoneData;
use AppBundle\Form\SettingClientZoneType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/client-zone")
 * @PermissionControllerName(SettingController::class)
 */
class SettingClientZoneController extends BaseController
{
    /**
     * @var OptionsManager
     */
    private $optionsManager;

    public function __construct(OptionsManager $optionsManager)
    {
        $this->optionsManager = $optionsManager;
    }

    /**
     * @Route("", name="setting_client_zone_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Client zone",
     *     path="System -> Settings -> Client zone",
     *     formTypes={SettingClientZoneType::class}
     * )
     */
    public function editAction(Request $request): Response
    {
        /** @var ClientZoneData $options */
        $options = $this->optionsManager->loadOptionsIntoDataClass(ClientZoneData::class);
        $oldOptions = clone $options;

        $form = $this->createForm(SettingClientZoneType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // If recurring payments were enabled before and now they are not (including autopay),
            // cancel all existing payment plans.
            if (
                ($oldOptions->subscriptionsEnabledCustom || $oldOptions->subscriptionsEnabledLinked)
                && (! $options->subscriptionsEnabledCustom && ! $options->subscriptionsEnabledLinked)
            ) {
                $paymentPlans = $this->em->getRepository(PaymentPlan::class)->findAll();
                $this->get(PaymentPlanFacade::class)->unsubscribeMultiple($paymentPlans);
            }

            $this->optionsManager->updateOptions($options);

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_client_zone_edit');
        }

        return $this->render(
            'setting/client_zone/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
