<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Tax;
use AppBundle\Form\Data\Settings\FeesData;
use AppBundle\Form\SettingFeesType;
use AppBundle\Security\Permission;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/billing/fees")
 */
class SettingFeesController extends BaseController
{
    /**
     * @Route("", name="setting_fees_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Fees",
     *     path="System -> Billing -> Fees",
     *     formTypes={SettingFeesType::class},
     *     extra={
     *         "Late fee",
     *         "Setup fee",
     *         "Early termination fee"
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var FeesData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(FeesData::class);

        if ($options->lateFeeTaxId) {
            $options->lateFeeTaxId = $this->em->getRepository(Tax::class)->findOneBy(
                [
                    'id' => $options->lateFeeTaxId,
                    'deletedAt' => null,
                ]
            );
        }

        if ($options->setupFeeTaxId) {
            $options->setupFeeTaxId = $this->em->getRepository(Tax::class)->findOneBy(
                [
                    'id' => $options->setupFeeTaxId,
                    'deletedAt' => null,
                ]
            );
        }

        if ($options->earlyTerminationFeeTaxId) {
            $options->earlyTerminationFeeTaxId = $this->em->getRepository(Tax::class)->findOneBy(
                [
                    'id' => $options->earlyTerminationFeeTaxId,
                    'deletedAt' => null,
                ]
            );
        }

        $form = $this->createForm(SettingFeesType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $options->lateFeeTaxId = $options->lateFeeTaxId
                ? $options->lateFeeTaxId->getId()
                : null;

            $options->setupFeeTaxId = $options->setupFeeTaxId
                ? $options->setupFeeTaxId->getId()
                : null;

            $options->earlyTerminationFeeTaxId = $options->earlyTerminationFeeTaxId
                ? $options->earlyTerminationFeeTaxId->getId()
                : null;

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_fees_edit');
        }

        return $this->render(
            'setting/fees/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
