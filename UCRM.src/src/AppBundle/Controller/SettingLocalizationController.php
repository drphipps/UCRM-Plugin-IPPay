<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\Locale;
use AppBundle\Entity\Timezone;
use AppBundle\Form\Data\Settings\LocalizationData;
use AppBundle\Form\SettingLocalizationType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/localization")
 * @PermissionControllerName(SettingController::class)
 */
class SettingLocalizationController extends BaseController
{
    /**
     * @Route("", name="setting_localization_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Localization",
     *     path="System -> Settings -> Localization",
     *     formTypes={SettingLocalizationType::class}
     * )
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var LocalizationData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(LocalizationData::class);
        if (null === $options->formatDecimalSeparator) {
            $options->formatUseDefaultDecimalSeparator = true;
        }
        if (null === $options->formatThousandsSeparator) {
            $options->formatUseDefaultThousandsSeparator = true;
        }

        if ($options->appLocale) {
            $options->appLocale = $this->em->getRepository(Locale::class)->findOneBy(
                [
                    'code' => $options->appLocale,
                ]
            );
        }

        if ($options->appTimezone) {
            $options->appTimezone = $this->em->getRepository(Timezone::class)->findOneBy(
                [
                    'name' => $options->appTimezone,
                ]
            );
        }

        $form = $this->createForm(SettingLocalizationType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $options->appLocale = $options->appLocale->getCode();
            $options->appTimezone = $options->appTimezone->getName();
            $options->formatDecimalSeparator = $options->formatUseDefaultDecimalSeparator
                ? null
                : ($options->formatDecimalSeparator ?? '');
            $options->formatThousandsSeparator = $options->formatUseDefaultThousandsSeparator
                ? null
                : ($options->formatThousandsSeparator ?? '');

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_localization_edit');
        }

        return $this->render(
            'setting/localization/edit.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
