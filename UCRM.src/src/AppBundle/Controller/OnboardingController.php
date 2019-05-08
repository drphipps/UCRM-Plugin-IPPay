<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\General;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/onboarding")
 */
class OnboardingController extends BaseController
{
    /**
     * @Route("/billing", name="onboarding_billing")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function billingAction(): RedirectResponse
    {
        $this->checkPermissions();
        $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_BILLING, '1');

        return $this->redirectToRoute('setting_billing_edit');
    }

    /**
     * @Route("/system", name="onboarding_system")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function systemAction(): RedirectResponse
    {
        $this->checkPermissions();
        $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_SYSTEM, '1');

        return $this->redirectToRoute('setting_application_edit');
    }

    /**
     * @Route("/mailer", name="onboarding_mailer")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function mailerAction(): RedirectResponse
    {
        $this->checkPermissions();
        $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_MAILER, '1');

        return $this->redirectToRoute('setting_mailer_edit');
    }

    /**
     * @Route("/close", name="onboarding_close")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function closeAction(): Response
    {
        $this->checkPermissions();
        $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_FINISHED, '1');

        return $this->createAjaxRedirectResponse('homepage');
    }

    private function checkPermissions(): void
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, ClientController::class);
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, ServiceController::class);
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SettingBillingController::class);
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SettingApplicationController::class);
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SettingMailerController::class);
    }
}
