<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\DataProvider\NotificationTemplateDataProvider;
use AppBundle\DataProvider\OrganizationDataProvider;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Facade\NotificationTemplateFacade;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Form\Data\Settings\TicketingData;
use AppBundle\Form\Data\Settings\TicketingNotificationData;
use AppBundle\Form\SettingTicketingNotificationType;
use AppBundle\Form\SettingTicketingType;
use AppBundle\Grid\Settings\TicketingCannedResponseGridFactory;
use AppBundle\Grid\Settings\TicketingGroupGridFactory;
use AppBundle\Grid\Settings\TicketingImapEmailBlacklistGridFactory;
use AppBundle\Grid\Settings\TicketingImapInboxGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Options;
use AppBundle\Service\OptionsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/system/settings/ticketing")
 * @PermissionControllerName(SettingController::class)
 */
class SettingTicketingController extends BaseController
{
    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var NotificationTemplateFacade
     */
    private $notificationTemplateFacade;

    /**
     * @var NotificationTemplateDataProvider
     */
    private $notificationTemplateDataProvider;

    public function __construct(
        OptionsFacade $optionsFacade,
        OptionsManager $optionsManager,
        NotificationTemplateFacade $notificationTemplateFacade,
        NotificationTemplateDataProvider $notificationTemplateDataProvider
    ) {
        $this->optionsFacade = $optionsFacade;
        $this->optionsManager = $optionsManager;
        $this->notificationTemplateFacade = $notificationTemplateFacade;
        $this->notificationTemplateDataProvider = $notificationTemplateDataProvider;
    }

    /**
     * @Route("", name="setting_ticketing_edit")
     * @Method({"GET", "POST"})
     * @Permission("view")
     * @Searchable(
     *     heading="Ticketing",
     *     path="System -> Settings -> Ticketing",
     *     formTypes={
     *         SettingTicketingType::class,
     *         SettingTicketingNotificationType::class
     *     },
     *     extra={
     *         "General configuration",
     *         "IMAP inboxes",
     *         "Automatic reply",
     *         "Automatic reply to new ticket",
     *         "User groups",
     *         "Canned responses",
     *         "Email blacklist",
     *     }
     * )
     */
    public function editAction(Request $request): Response
    {
        /** @var TicketingData $options */
        $options = $this->optionsManager->loadOptionsIntoDataClass(TicketingData::class);

        $form = $this->createForm(SettingTicketingType::class, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->optionsFacade->handleUpdateTicketingSetting($options);

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_ticketing_edit');
        }

        /** @var TicketingNotificationData $notificationData */
        $notificationData = $this->optionsManager->loadOptionsIntoDataClass(TicketingNotificationData::class);
        $notificationData->automaticReplyNotificationTemplate = $this->notificationTemplateDataProvider->getTemplate(NotificationTemplate::TICKET_AUTOMATIC_REPLY);

        $notificationForm = $this->createForm(SettingTicketingNotificationType::class, $notificationData);
        $notificationForm->handleRequest($request);
        if ($notificationForm->isSubmitted()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);
        }

        if ($notificationForm->isSubmitted() && $notificationForm->isValid()) {
            $this->optionsManager->updateOptions($notificationData);
            $this->notificationTemplateFacade->handleUpdateTemplates([$notificationData->automaticReplyNotificationTemplate]);

            $this->addTranslatedFlash('success', 'Settings have been saved.');

            return $this->redirectToRoute('setting_ticketing_edit');
        }

        $gridTicketingGroups = $this->get(TicketingGroupGridFactory::class)->create();
        if ($parameters = $gridTicketingGroups->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $gridTicketingCannedResponses = $this->get(TicketingCannedResponseGridFactory::class)->create();
        if ($parameters = $gridTicketingCannedResponses->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $gridImapInboxes = $this->get(TicketingImapInboxGridFactory::class)->create();
        if ($parameters = $gridImapInboxes->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $gridImapEmailBlacklist = $this->get(TicketingImapEmailBlacklistGridFactory::class)->create();
        if ($parameters = $gridImapEmailBlacklist->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        $supportEmailAddress = $this->get(Options::class)->get(Option::SUPPORT_EMAIL_ADDRESS);
        $systemEmailAddresses = array_unique(array_merge(
            $this->get(OrganizationDataProvider::class)->getEmails(),
            $supportEmailAddress ? [$supportEmailAddress] : []
        ));

        return $this->render(
            'setting/ticketing/edit.html.twig',
            [
                'form' => $form->createView(),
                'notificationForm' => $notificationForm->createView(),
                'gridTicketingGroups' => $gridTicketingGroups,
                'gridTicketingCannedResponses' => $gridTicketingCannedResponses,
                'gridImapInboxes' => $gridImapInboxes,
                'gridImapEmailBlacklist' => $gridImapEmailBlacklist,
                'systemEmailAddresses' => $systemEmailAddresses,
            ]
        );
    }
}
