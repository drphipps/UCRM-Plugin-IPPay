<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Encryption;
use AppBundle\Util\DateTimeFactory;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\DataProvider\TicketImapInboxDataProvider;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\Form\TicketImapInboxConfigurationType;
use TicketingBundle\Form\TicketImapInboxType;
use TicketingBundle\Service\Facade\TicketImapInboxFacade;

/**
 * @Route("/system/settings/ticketing/imap-inboxes")
 * @PermissionControllerName(SettingController::class)
 */
class SettingTicketingImapInboxesController extends BaseController
{
    /**
     * @Route("/new", name="setting_ticketing_imap_inbox_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEdit($request);
    }

    /**
     * @Route("/{id}/edit", name="setting_ticketing_imap_inbox_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, TicketImapInbox $ticketImapInbox): Response
    {
        return $this->handleNewEdit($request, $ticketImapInbox);
    }

    /**
     * @Route("/{id}/configuration", name="setting_ticketing_imap_inbox_configuration", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function configurationAction(Request $request, TicketImapInbox $ticketImapInbox): Response
    {
        $ticketImapInboxFacade = $this->get(TicketImapInboxFacade::class);
        if (! $ticketImapInbox->getImportStartDate()) {
            $ticketImapInbox->setImportStartDate(DateTimeFactory::createFromInterface($ticketImapInboxFacade->getDefaultImportStartDate()));
        }

        $form = $this->createForm(
            TicketImapInboxConfigurationType::class,
            $ticketImapInbox,
            [
                'action' => $this->generateUrl(
                    'setting_ticketing_imap_inbox_configuration',
                    ['id' => $ticketImapInbox->getId()]
                ),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketImapInboxFacade->handleUpdate($ticketImapInbox);
            $this->addTranslatedFlash('success', 'Item has been saved.');

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_imap_inbox_configuration_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="setting_ticketing_imap_inbox_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function deleteAction(TicketImapInbox $ticketImapInbox): RedirectResponse
    {
        $this->get(TicketImapInboxFacade::class)->handleDelete($ticketImapInbox);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('setting_ticketing_edit');
    }

    private function handleNewEdit(Request $request, ?TicketImapInbox $ticketImapInbox = null): Response
    {
        $ticketImapInboxFacade = $this->get(TicketImapInboxFacade::class);
        $isEdit = (bool) $ticketImapInbox;
        if (! $isEdit) {
            $ticketImapInbox = new TicketImapInbox();
            if (! $this->get(TicketImapInboxDataProvider::class)->findDefault()) {
                $ticketImapInbox->setIsDefault(true);
            }
            $ticketImapInbox->setImportStartDate(
                DateTimeFactory::createFromInterface($ticketImapInboxFacade->getDefaultImportStartDate())
            );
        }

        $ticketImapInboxOld = clone $ticketImapInbox;

        $formAction = $isEdit
            ? $this->generateUrl('setting_ticketing_imap_inbox_edit', ['id' => $ticketImapInbox->getId()])
            : $this->generateUrl('setting_ticketing_imap_inbox_new');

        $form = $this->createForm(
            TicketImapInboxType::class,
            $ticketImapInbox,
            [
                'action' => $formAction,
                'include_is_default_option' => ! $ticketImapInbox->isDefault(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketImapInbox->setPassword($this->get(Encryption::class)->encrypt($ticketImapInbox->getPassword()));
            if ($isEdit) {
                if (
                    $ticketImapInbox->getServerName() !== $ticketImapInboxOld->getServerName()
                    || $ticketImapInbox->getEmailAddress() !== $ticketImapInboxOld->getEmailAddress()
                    || $ticketImapInbox->getUsername() !== $ticketImapInboxOld->getUsername()
                ) {
                    $ticketImapInbox->setImportStartDate(null);
                }
                $ticketImapInboxFacade->handleUpdate($ticketImapInbox);
                $this->addTranslatedFlash('success', 'Item has been saved.');
            } else {
                $ticketImapInboxFacade->handleCreate($ticketImapInbox);
                $this->addTranslatedFlash('success', 'Item has been created.');
            }

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_imap_inbox_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
