<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Entity\TicketImapEmailBlacklist;
use TicketingBundle\Form\Data\TicketImapEmailBlacklistMultipleData;
use TicketingBundle\Form\TicketImapEmailBlacklistMultipleType;
use TicketingBundle\Form\TicketImapEmailBlacklistType;
use TicketingBundle\Service\Facade\TicketFacade;
use TicketingBundle\Service\Facade\TicketImapEmailBlacklistFacade;

/**
 * @Route("/system/settings/ticketing/imap-email-blacklist")
 * @PermissionControllerName(SettingController::class)
 */
class SettingTicketingImapEmailBlacklistController extends BaseController
{
    /**
     * @Route("/new", name="setting_ticketing_imap_email_blacklist_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        $blacklistData = new TicketImapEmailBlacklistMultipleData();
        $form = $this->createForm(
            TicketImapEmailBlacklistMultipleType::class,
            $blacklistData,
            [
                'action' => $this->generateUrl('setting_ticketing_imap_email_blacklist_new'),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTicketEmailBlacklists = [];
            $emailAddresses = explode(PHP_EOL, $blacklistData->emailAddresses);
            foreach ($emailAddresses as $emailAddress) {
                $emailAddress = trim($emailAddress);
                if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                    $ticketImapEmailBlacklist = new TicketImapEmailBlacklist();
                    $ticketImapEmailBlacklist->setEmailAddress($emailAddress);

                    $newTicketEmailBlacklists[] = $ticketImapEmailBlacklist;
                }
            }

            $createdItems = 0;
            if ($newTicketEmailBlacklists) {
                $createdItems = $this->get(TicketImapEmailBlacklistFacade::class)->createMultiple($newTicketEmailBlacklists);
                $this->addTranslatedFlash('success', 'Item has been created.');

                if ($blacklistData->deleteTickets) {
                    $this->get(TicketFacade::class)->deleteByEmailFromAddresses(
                        array_map(
                            function (TicketImapEmailBlacklist $ticketImapEmailBlacklist) {
                                return $ticketImapEmailBlacklist->getEmailAddress();
                            },
                            $newTicketEmailBlacklists
                        )
                    );
                }
            }

            if (count($emailAddresses) !== $createdItems) {
                $this->addTranslatedFlash(
                    'warning',
                    'Some email addresses were not imported, because they were invalid or already existed.'
                );
            }

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_imap_email_blacklist_multiple_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/edit", name="setting_ticketing_imap_email_blacklist_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, TicketImapEmailBlacklist $ticketImapEmailBlacklist): Response
    {
        $form = $this->createForm(
            TicketImapEmailBlacklistType::class,
            $ticketImapEmailBlacklist,
            [
                'action' => $this->generateUrl(
                    'setting_ticketing_imap_email_blacklist_edit',
                    ['id' => $ticketImapEmailBlacklist->getId()]
                ),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(TicketImapEmailBlacklistFacade::class)->handleUpdate($ticketImapEmailBlacklist);
            $this->addTranslatedFlash('success', 'Item has been saved.');

            if ($form->get('deleteTickets')->getData()) {
                $this->get(TicketFacade::class)->deleteByEmailFromAddresses([$form->get('emailAddress')->getData()]);
            }

            return $this->createAjaxRedirectResponse('setting_ticketing_edit');
        }

        return $this->render(
            'setting/ticketing/ticketing_imap_email_blacklist_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="setting_ticketing_imap_email_blacklist_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("edit")
     * @CsrfToken()
     */
    public function deleteAction(TicketImapEmailBlacklist $ticketImapEmailBlacklist): RedirectResponse
    {
        $this->get(TicketImapEmailBlacklistFacade::class)->handleDelete($ticketImapEmailBlacklist);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->redirectToRoute('setting_ticketing_edit');
    }
}
