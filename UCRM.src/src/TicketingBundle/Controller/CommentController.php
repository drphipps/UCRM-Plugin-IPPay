<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Exception\EmailNotFoundException;
use AppBundle\Exception\ImapConnectionException;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\ImapMessageParser;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Exception\ImapGetmailboxesException;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Service\Factory\TicketImapModelFactory;

/**
 * @Route("/ticketing/comment")
 * @PermissionControllerName(TicketController::class)
 */
class CommentController extends BaseController
{
    /**
     * @Route("/{id}/mail", name="ticketing_comment_mail_show", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
     *
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function commentMailShowAction(TicketComment $ticketComment): Response
    {
        try {
            if (! $inbox = $ticketComment->getInbox()) {
                throw new EmailNotFoundException('Settings for IMAP Inbox was deleted.');
            }
            $mail = $this->get(TicketImapModelFactory::class)->create($inbox)
                ->getMailByTicketComment($ticketComment);
        } catch (ImapConnectionException | AuthenticationFailedException | MessageDoesNotExistException | ImapGetmailboxesException $exception) {
            return $this->render(
                '@Ticketing/comment/mail_not_found_modal.html.twig',
                [
                    'exception' => $exception,
                ]
            );
        } catch (EmailNotFoundException $exception) {
            $this->addTranslatedFlash('error', 'Email is not available.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            '@Ticketing/comment/mail_modal.html.twig',
            [
                'rawHeaders' => $mail->getRawHeaders(),
                'bodyHtml' => ImapMessageParser::getBodyHtml($mail),
                'bodyText' => ImapMessageParser::getBodyText($mail),
            ]
        );
    }
}
