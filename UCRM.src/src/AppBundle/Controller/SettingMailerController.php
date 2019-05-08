<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Form\Data\Settings\EmailAddressesData;
use AppBundle\Form\Data\Settings\MailerData;
use AppBundle\Form\Data\Settings\MailerLimiterData;
use AppBundle\Form\SettingEmailAddressType;
use AppBundle\Form\SettingMailerLimiterType;
use AppBundle\Form\SettingMailerType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Email\EmailDirectSender;
use AppBundle\Service\OptionsManager;
use AppBundle\Util\Helpers;
use AppBundle\Util\Message;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/mailer")
 * @PermissionControllerName(SettingController::class)
 */
class SettingMailerController extends BaseController
{
    /**
     * @Route("", name="setting_mailer_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Mailer",
     *     path="System -> Settings -> Mailer",
     *     formTypes={
     *         SettingMailerType::class,
     *         SettingMailerLimiterType::class,
     *         SettingEmailAddressType::class
     *     },
     *     extra={
     *         "SMTP configuration",
     *         "Addresses",
     *         "Email addresses configuration check",
     *         "Throttler",
     *         "AntiFlood"
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        $optionsManager = $this->get(OptionsManager::class);
        $optionsFacade = $this->get(OptionsFacade::class);
        $formFactory = $this->get('form.factory');

        /** @var MailerData $mailerOptions */
        $mailerOptions = $optionsManager->loadOptionsIntoDataClass(MailerData::class);

        /** @var MailerLimiterData $limiterOptions */
        $limiterOptions = $optionsManager->loadOptionsIntoDataClass(MailerLimiterData::class);
        $optionsFacade->loadMailerLimiterSettingsDefaults($limiterOptions);

        /** @var EmailAddressesData $addressesOptions */
        $addressesOptions = $optionsManager->loadOptionsIntoDataClass(EmailAddressesData::class);

        $mailerForm = $formFactory
            ->createNamedBuilder(
                'mailerForm',
                SettingMailerType::class,
                $mailerOptions
            )
            ->getForm();

        $limiterForm = $formFactory
            ->createNamedBuilder(
                'limiterForm',
                SettingMailerLimiterType::class,
                $limiterOptions
            )
            ->getForm();

        $addressesForm = $formFactory
            ->createNamedBuilder(
                'addressesForm',
                SettingEmailAddressType::class,
                $addressesOptions
            )
            ->getForm();

        $mailerForm->handleRequest($request);
        $limiterForm->handleRequest($request);
        $addressesForm->handleRequest($request);

        if ($mailerForm->isSubmitted() || $limiterForm->isSubmitted() || $addressesForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if (
            Helpers::isDemo()
            && ($mailerForm->isSubmitted() || $limiterForm->isSubmitted() || $addressesForm->isSubmitted())
        ) {
            $this->addTranslatedFlash('error', 'Mailer settings can\'t be changed in the demo.');

            return $this->redirectToRoute('setting_mailer_edit');
        }

        if ($mailerForm->isSubmitted() && $mailerForm->isValid()) {
            $optionsFacade->handleUpdateMailerSettings($mailerOptions);
            $this->get(OptionsFacade::class)->updateGeneral(General::ONBOARDING_HOMEPAGE_MAILER, '1');
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_mailer_edit');
        }

        if ($limiterForm->isSubmitted() && $limiterForm->isValid()) {
            $optionsFacade->handleUpdateMailerLimiterSettings($limiterOptions);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_mailer_edit');
        }

        if ($addressesForm->isSubmitted() && $addressesForm->isValid()) {
            $optionsManager->updateOptions($addressesOptions);
            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_mailer_edit');
        }

        return $this->render(
            'setting/mailer/edit.html.twig',
            [
                'mailerForm' => $mailerForm->createView(),
                'addressesForm' => $addressesForm->createView(),
                'limiterForm' => $limiterForm->createView(),
                'isGmail' => $mailerOptions->mailerTransport === Option::MAILER_TRANSPORT_GMAIL,
                'isSenderSet' => (bool) $this->getOption(Option::MAILER_SENDER_ADDRESS),
                'isSupportSet' => (bool) $this->getOption(Option::SUPPORT_EMAIL_ADDRESS),
                'isNotificationSet' => (bool) $this->getOption(Option::NOTIFICATION_EMAIL_ADDRESS),
                'isTicketingEnabled' => (bool) $this->getOption(Option::TICKETING_ENABLED),
            ]
        );
    }

    /**
     * @Route("/send-test-message", name="setting_send_test_message")
     * @Method("GET")
     * @CsrfToken()
     * @Permission("view")
     */
    public function sendTestMessageAction(): JsonResponse
    {
        $mailerOk = true;
        $exceptionMessage = null;

        try {
            $emailSender = $this->get(EmailDirectSender::class);
            $address = $this->getOption(Option::MAILER_SENDER_ADDRESS) ?: null;

            $message = new Message();
            $message->setSubject('TEST MESSAGE');
            $message->setFrom($address);
            $message->setSender($address);
            $message->setTo($address);
            $message->setBody(
                'TEST MESSAGE BODY',
                'text/html'
            );

            $emailSender->send($message);
        } catch (\Throwable $e) {
            $mailerOk = false;
            $exceptionMessage = $e->getMessage();
        }

        if ($mailerOk) {
            return new JsonResponse(
                [
                    'status' => 'ok',
                    'message' => $this->trans('Test message successfully sent.'),
                ]
            );
        }

        return new JsonResponse(
            [
                'status' => 'failed',
                'message' => $exceptionMessage ?: $this->trans('Test message could not be sent.'),
            ]
        );
    }
}
