<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Factory;

use AppBundle\Entity\Client;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Exception\OptionNotFoundException;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Util\Message;
use AppBundle\Util\Notification;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\Entity\TicketImapInbox;
use TicketingBundle\FileManager\CommentAttachmentFileManager;
use TicketingBundle\Handler\TicketImapImportHandler;
use TicketingBundle\Interfaces\TicketActivityWithEmailInterface;

class NotificationEmailMessageFactory
{
    /**
     * @var CommentAttachmentFileManager
     */
    private $commentAttachmentFileManager;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        CommentAttachmentFileManager $commentAttachmentFileManager,
        EntityManager $em,
        NotificationFactory $notificationFactory,
        Options $options,
        PublicUrlGenerator $publicUrlGenerator,
        \Twig_Environment $twig,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->commentAttachmentFileManager = $commentAttachmentFileManager;
        $this->em = $em;
        $this->notificationFactory = $notificationFactory;
        $this->options = $options;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->twig = $twig;
        $this->router = $router;
        $this->translator = $translator;
    }

    public function createNewTicketNotificationMessageForClient(Ticket $ticket): Message
    {
        $notification = $this->prepareNotificationTemplateForClient(
            $ticket,
            NotificationTemplate::TICKET_CREATED_BY_USER
        );
        $notification->setTicketMessage($ticket->getComments()->last()->getBody() ?? '');
        $notification->setTicketCommentAttachments($ticket->getComments()->last()->getAttachments());

        return $this->prepareMessageForClient(
            $ticket,
            $notification
        );
    }

    public function createAutomaticReplyMessageFromImapTicket(Ticket $ticket): Message
    {
        $notificationTemplate = $this->em
            ->getRepository(NotificationTemplate::class)
            ->find(NotificationTemplate::TICKET_AUTOMATIC_REPLY);

        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setTicket($ticket);
        $notification->setTicketMessage($ticket->getComments()->last()->getBody() ?? '');
        $notification->setTicketCommentAttachments($ticket->getComments()->last()->getMailAttachments());

        if ($ticket->getClient()) {
            $message = $this->prepareMessageForClient(
                $ticket,
                $notification
            );
        } else {
            $message = $this->prepareMessageForEmail(
                $ticket,
                $notification
            );
        }

        $this->addMessageHeaders($message, $ticket);

        return $message;
    }

    public function createStatusChangedMessage(Ticket $ticket): Message
    {
        $message = $this->prepareMessageForClient(
            $ticket,
            $this->prepareNotificationTemplateForClient(
                $ticket,
                NotificationTemplate::TICKET_CHANGED_STATUS
            )
        );

        $this->addMessageHeaders($message, $ticket);

        return $message;
    }

    public function createNewTicketCommentFromUserMessage(TicketComment $ticketComment): Message
    {
        $ticket = $ticketComment->getTicket();

        if ($ticket->getClient()) {
            $notification = $this->prepareNotificationTemplateForClient(
                $ticket,
                $this->options->get(Option::TICKETING_ENABLED) && $ticket->getEmailFromAddress()
                    ? NotificationTemplate::TICKET_COMMENTED_BY_USER_WITH_IMAP
                    : NotificationTemplate::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP
            );
            $notification->setTicketMessage((string) $ticketComment->getBody());
            $notification->setTicketCommentAttachments($ticketComment->getAttachments());

            $message = $this->prepareMessageForClient(
                $ticket,
                $notification
            );
        } else {
            $notification = $this->prepareNotificationTemplateForEmail(
                $ticket,
                NotificationTemplate::TICKET_COMMENTED_BY_USER_TO_EMAIL
            );
            $notification->setTicketMessage((string) $ticketComment->getBody());
            $notification->setTicketCommentAttachments($ticketComment->getAttachments());

            $message = $this->prepareMessageForEmail(
                $ticket,
                $notification
            );

            $this->attachTicketCommentAttachments($message, $ticketComment->getAttachments());
        }

        $this->addMessageHeaders($message, $ticketComment->getTicket());

        return $message;
    }

    private function prepareMessageForClient(Ticket $ticket, Notification $notification): Message
    {
        $client = $ticket->getClient();
        assert($client instanceof Client);
        $organization = $client->getOrganization();
        assert($organization instanceof Organization);
        $message = new Message();
        $message->setClient($client);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());

        $this->setReplyTo($message, $ticket);

        $message->setSender(
            $this->options->get(Option::MAILER_SENDER_ADDRESS)
                ?: $organization->getEmail()
                ?: null
        );
        $message->setTo($client->getContactEmails());
        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        return $message;
    }

    private function prepareMessageForEmail(Ticket $ticket, Notification $notification): Message
    {
        if (! $this->options->get(Option::SUPPORT_EMAIL_ADDRESS)) {
            // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
            // the link is internal, so there is no security concern
            $ex = new OptionNotFoundException(
                'Email not sent. Support email address is not set! <a href="%link%" target="_blank">Set it here.</a>'
            );
            $ex->setParameters(
                ['%link%' => $this->router->generate('setting_mailer_edit') . '#setting-addresses-form']
            );
            throw $ex;
        }

        $message = new Message();
        $message->setSubject($notification->getSubject());
        $message->setFrom($this->options->get(Option::SUPPORT_EMAIL_ADDRESS));

        $this->setReplyTo($message, $ticket);

        $message->setTo([$ticket->getEmailFromAddress() => $ticket->getEmailFromName()]);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS));

        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        return $message;
    }

    /**
     * @throws OptionNotFoundException
     */
    public function createNotificationMessageForSupportNewTicket(Ticket $ticket): Message
    {
        $supportEmail = $this->options->get(Option::SUPPORT_EMAIL_ADDRESS);
        $supportName = null;
        $client = $ticket->getClient();
        if (! $supportEmail && $client) {
            $supportEmail = $client->getOrganization()->getEmail();
            $supportName = $client->getOrganization()->getName();
        }

        if (! $supportEmail) {
            // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
            // the link is internal, so there is no security concern
            $ex = new OptionNotFoundException(
                'Email not sent. Support email address is not set! <a href="%link%" target="_blank">Set it here.</a>'
            );
            $ex->setParameters(
                ['%link%' => $this->router->generate('setting_mailer_edit') . '#setting-addresses-form']
            );
            throw $ex;
        }

        $siteName = $this->options->get(Option::SITE_NAME);
        $emailSubject = $this->translator->trans(
            'New ticket %number%',
            [
                '%number%' => sprintf('#%s', $ticket->getId()),
            ]
        );
        if ($client) {
            $emailSubject .= ' - ' . $this->getClientName($client);
        }
        $emailSubject = $siteName ? sprintf('[%s] %s', $siteName, $emailSubject) : $emailSubject;

        $message = new Message();
        $message->setClient($client);
        $message->setSubject($emailSubject);
        $message->setTo($supportEmail);
        $message->setFrom($supportEmail, $supportName);
        $message->setSender(
            $this->options->get(Option::MAILER_SENDER_ADDRESS)
                ?: ($client ? $client->getOrganization()->getEmail() : null)
        );
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Mailer', TicketImapImportHandler::X_MAILER_HEADER);
        $message->setBody(
            $this->twig->render(
                '@Ticketing/client/new_ticket_email.html.twig',
                [
                    'ticket' => $ticket,
                    'message' => (string) $ticket->getComments()->last()->getBody(),
                    'client' => $client,
                    'byUser' => $ticket->getActivity()->current()->getUser(),
                ]
            ),
            'text/html'
        );

        if ($client && $contactEmails = $client->getContactEmails()) {
            $message->addReplyTo(reset($contactEmails), $client->getNameForView());
        }

        return $message;
    }

    public function createNotificationMessageForSupportNewTicketComment(TicketComment $ticketComment): Message
    {
        $supportEmail = $this->options->get(Option::SUPPORT_EMAIL_ADDRESS);
        $supportName = null;
        $ticket = $ticketComment->getTicket();
        $client = $ticket->getClient();
        if (! $supportEmail && $client) {
            $supportEmail = $client->getOrganization()->getEmail();
            $supportName = $client->getOrganization()->getName();
        }

        if (! $supportEmail) {
            // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
            // the link is internal, so there is no security concern
            $ex = new OptionNotFoundException(
                'Email not sent. Support email address is not set! <a href="%link%" target="_blank">Set it here.</a>'
            );
            $ex->setParameters(
                ['%link%' => $this->router->generate('setting_mailer_edit') . '#setting-addresses-form']
            );
            throw $ex;
        }

        $siteName = $this->options->get(Option::SITE_NAME);
        $emailSubject = $this->translator->trans(
            'New comment for ticket %number%',
            [
                '%number%' => sprintf('#%s', $ticket->getId()),
            ]
        );
        if ($client) {
            $emailSubject .= ' - ' . $this->getClientName($client);
        }
        $emailSubject = $siteName ? sprintf('[%s] %s', $siteName, $emailSubject) : $emailSubject;

        $message = new Message();
        $message->setClient($client);
        $message->setSubject($emailSubject);
        $message->setTo($supportEmail);
        $message->setFrom($supportEmail, $supportName);
        $message->setSender(
            $this->options->get(Option::MAILER_SENDER_ADDRESS)
                ?: ($client ? $client->getOrganization()->getEmail() : null)
        );
        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Mailer', TicketImapImportHandler::X_MAILER_HEADER);
        $message->setBody(
            $this->twig->render(
                '@Ticketing/client/new_ticket_comment_email.html.twig',
                [
                    'ticket' => $ticket,
                    'message' => $ticketComment->getBody(),
                    'client' => $client,
                    'byUser' => $ticketComment->getUser(),
                ]
            ),
            'text/html'
        );

        if ($client && $contactEmails = $client->getContactEmails()) {
            $message->addReplyTo(reset($contactEmails), $client->getNameForView());
        }

        return $message;
    }

    private function addMessageHeaders(Message $message, Ticket $ticket): void
    {
        $replies = [];
        foreach ($ticket->getActivity() as $activity) {
            if ($activity instanceof TicketActivityWithEmailInterface && $activity->getEmailId()) {
                $replies[$activity->getCreatedAt()->getTimestamp()] = '<' . $activity->getEmailId() . '>';
            }
        }
        ksort($replies);
        $headers = $message->getHeaders();
        if ($replies) {
            $headers->addTextHeader('References', implode(' ', $replies));
            $headers->addTextHeader('In-Reply-To', end($replies));
        }
    }

    private function prepareNotificationTemplateForClient(Ticket $ticket, int $notificationTemplateType): Notification
    {
        $notificationTemplate = $this->em
            ->getRepository(NotificationTemplate::class)
            ->findOneBy(
                [
                    'type' => $notificationTemplateType,
                ]
            );

        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setClient($ticket->getClient());
        $notification->setTicket($ticket);
        $notification->setTicketUrl(
            $this->publicUrlGenerator->generate(
                'client_zone_support_index',
                [
                    'ticketId' => $ticket->getId(),
                ]
            )
        );

        return $notification;
    }

    private function prepareNotificationTemplateForEmail(Ticket $ticket, int $notificationTemplateType): Notification
    {
        $notificationTemplate = $this->em
            ->getRepository(NotificationTemplate::class)
            ->findOneBy(
                [
                    'type' => $notificationTemplateType,
                ]
            );

        $notification = $this->notificationFactory->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setTicket($ticket);

        return $notification;
    }

    private function attachTicketCommentAttachments(Message $message, Collection $attachments): void
    {
        /** @var TicketCommentAttachment $attachment */
        foreach ($attachments as $attachment) {
            $message->attach(
                \Swift_Attachment::fromPath(
                    $this->commentAttachmentFileManager->getFilePath($attachment),
                    $attachment->getMimeType()
                )->setFilename($attachment->getOriginalFilename())
            );
        }
    }

    private function getClientName(Client $client): string
    {
        $clientIdType = $this->options->get(Option::CLIENT_ID_TYPE);

        return sprintf(
            '%s (%s: %s)',
            $client->getNameForView(),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $this->translator->trans('ID')
                : $this->translator->trans('Custom ID'),
            $clientIdType === Option::CLIENT_ID_TYPE_DEFAULT
                ? $client->getId()
                : $client->getUserIdent()
        );
    }

    private function setReplyTo(Message $message, Ticket $ticket): void
    {
        if ($ticket->getGroup()) {
            foreach ($ticket->getGroup()->getTicketImapInboxes() as $imapInbox) {
                if ($imapInbox->getEmailAddress()) {
                    $message->setReplyTo($imapInbox->getEmailAddress());

                    return;
                }
            }
        }

        $inbox = $this->em->getRepository(TicketImapInbox::class)->findOneBy(['isDefault' => true]);
        if ($inbox) {
            $message->setReplyTo($inbox->getEmailAddress());
        }
    }
}
