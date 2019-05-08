<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Facade\CustomCssFacade;
use AppBundle\Facade\CustomFaviconFacade;
use AppBundle\Facade\LoginBannerFacade;
use AppBundle\FileManager\CustomCssFileManager;
use AppBundle\Form\CustomCssType;
use AppBundle\Form\CustomFaviconType;
use AppBundle\Form\Data\CustomCssData;
use AppBundle\Form\Data\FaviconUploadData;
use AppBundle\Form\Data\LoginBannerUploadData;
use AppBundle\Form\LoginBannerType;
use AppBundle\Security\Permission;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/customization/appearance")
 */
class AppearanceController extends BaseController
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var CustomCssFileManager
     */
    private $customCssFileManager;

    /**
     * @var CustomCssFacade
     */
    private $customCssFacade;

    /**
     * @var CustomFaviconFacade
     */
    private $customFaviconFacade;

    /**
     * @var LoginBannerFacade
     */
    private $loginBannerFacade;

    public function __construct(
        FormFactoryInterface $formFactory,
        CustomCssFileManager $customCssFileManager,
        CustomCssFacade $customCssFacade,
        CustomFaviconFacade $customFaviconFacade,
        LoginBannerFacade $loginBannerFacade
    ) {
        $this->formFactory = $formFactory;
        $this->customCssFileManager = $customCssFileManager;
        $this->customCssFacade = $customCssFacade;
        $this->customFaviconFacade = $customFaviconFacade;
        $this->loginBannerFacade = $loginBannerFacade;
    }

    /**
     * @Route("", name="appearance_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Appearance",
     *     path="System -> Customization -> Appearance",
     *     extra={
     *         "Custom CSS",
     *         "Favicon",
     *         "Login banner",
     *     }
     * )
     */
    public function indexAction(Request $request): Response
    {
        $customCssData = new CustomCssData();
        $customCssForm = $this->createCustomCssForm($customCssData);
        if ($response = $this->handleCustomCssForm($request, $customCssForm, $customCssData)) {
            return $response;
        }

        $faviconUploadData = new FaviconUploadData();
        $customFaviconForm = $this->createCustomFaviconForm($faviconUploadData);
        if ($response = $this->handleCustomFaviconForm($request, $customFaviconForm, $faviconUploadData)) {
            return $response;
        }

        $loginBannerUploadData = new LoginBannerUploadData();
        $loginBannerForm = $this->createLoginBannerForm($loginBannerUploadData);
        if ($response = $this->handleLoginBannerForm($request, $loginBannerForm, $loginBannerUploadData)) {
            return $response;
        }

        return $this->render(
            'appearance/index.html.twig',
            [
                'customCssForm' => $customCssForm->createView(),
                'customFaviconForm' => $customFaviconForm->createView(),
                'loginBannerForm' => $loginBannerForm->createView(),
            ]
        );
    }

    /**
     * @Route("/remove-custom-favicon", name="appearance_remove_custom_favicon")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function removeCustomFaviconAction(): Response
    {
        $this->customFaviconFacade->handleDelete();

        $this->invalidateTemplate(
            'favicon-preview',
            'appearance/components/favicon_preview.html.twig',
            [],
            true
        );

        $this->addTranslatedFlash('success', 'Image has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/remove-login-banner", name="appearance_remove_login_banner")
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function removeLoginBannerAction(): Response
    {
        $this->loginBannerFacade->handleDelete();

        $this->invalidateTemplate(
            'login-banner-preview',
            'appearance/components/login_banner_preview.html.twig',
            [],
            true
        );

        $this->addTranslatedFlash('success', 'Image has been deleted.');

        return $this->createAjaxResponse();
    }

    private function createCustomCssForm(CustomCssData $data): FormInterface
    {
        $data->css = $this->customCssFileManager->get();
        $form = $this->formFactory->createNamed(
            'custom_css_form',
            CustomCssType::class,
            $data
        );

        return $form;
    }

    private function handleCustomCssForm(Request $request, FormInterface $form, CustomCssData $data): ?Response
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->customCssFacade->handleSave($data->css ?? '');

            $this->addTranslatedFlash('success', 'Custom CSS saved.');

            return $this->redirectToRoute('appearance_index');
        }

        return null;
    }

    private function createCustomFaviconForm(FaviconUploadData $data): FormInterface
    {
        return $this->formFactory->createNamed(
            'custom_favicon_form',
            CustomFaviconType::class,
            $data
        );
    }

    private function handleCustomFaviconForm(Request $request, FormInterface $form, FaviconUploadData $data): ?Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->customFaviconFacade->handleSave($data->favicon);

            $this->addTranslatedFlash('success', 'Custom favicon saved.');

            return $this->redirectToRoute('appearance_index');
        }

        return null;
    }

    private function createLoginBannerForm(LoginBannerUploadData $data): FormInterface
    {
        return $this->formFactory->createNamed(
            'login_banner_form',
            LoginBannerType::class,
            $data
        );
    }

    private function handleLoginBannerForm(
        Request $request,
        FormInterface $form,
        LoginBannerUploadData $data
    ): ?Response {
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            if (Helpers::isDemo()) {
                $this->addTranslatedFlash('error', 'This feature is not available in the demo.');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->loginBannerFacade->handleSave($data->loginBanner);

            $this->addTranslatedFlash('success', 'Login banner saved.');

            return $this->redirectToRoute('appearance_index');
        }

        return null;
    }
}
