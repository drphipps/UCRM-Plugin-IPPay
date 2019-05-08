<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Form\Data\Settings\OAuthData;
use AppBundle\Form\SettingOAuthType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\OptionsManager;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/oauth")
 * @PermissionControllerName(SettingController::class)
 */
class SettingOAuthController extends BaseController
{
    /**
     * @Route("", name="setting_oauth_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(heading="OAuth", path="System -> Settings -> OAuth", formTypes={SettingOAuthType::class})
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var OAuthData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(OAuthData::class);

        $form = $this->createForm(SettingOAuthType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');

                return $this->redirectToRoute('setting_oauth_edit');
            }

            if ($options->googleOAuthSecretFile) {
                $options->googleOAuthSecret = file_get_contents($options->googleOAuthSecretFile->getPathname());
            }

            $optionsManager->updateOptions($options);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_oauth_edit');
        }

        try {
            $googleRedirectUrl = $this->get(PublicUrlGenerator::class)->generate(
                'google_oauth_callback',
                [],
                false
            );
        } catch (PublicUrlGeneratorException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->render(
            'setting/oauth/edit.html.twig',
            [
                'form' => $form->createView(),
                'googleRedirectUrl' => $googleRedirectUrl ?? '',
                'hasGoogleOAuthSecret' => (bool) $options->googleOAuthSecret,
            ]
        );
    }

    /**
     * @Route("/delete-google-secret", name="setting_oauth_delete_google_secret")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteGoogleOAuthSecretAction(): Response
    {
        $optionsManager = $this->get(OptionsManager::class);

        /** @var OAuthData $options */
        $options = $optionsManager->loadOptionsIntoDataClass(OAuthData::class);
        $options->googleOAuthSecret = null;
        $optionsManager->updateOptions($options);
        $this->addTranslatedFlash('success', 'Google OAuth secret has been deleted.');

        return $this->redirectToRoute('setting_oauth_edit');
    }
}
