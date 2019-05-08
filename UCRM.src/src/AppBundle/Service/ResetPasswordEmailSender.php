<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ResetPasswordEmailSender
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PublicUrlGenerator
     */
    private $publicUrlGenerator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var EngineInterface
     */
    private $twigEngine;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var EmailLogger
     */
    private $emailLogger;

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    public function __construct(
        EntityManager $em,
        PublicUrlGenerator $publicUrlGenerator,
        Options $options,
        EngineInterface $twigEngine,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        ActionLogger $actionLogger,
        TranslatorInterface $translator,
        NotificationFactory $notificationFactory
    ) {
        $this->em = $em;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->options = $options;
        $this->twigEngine = $twigEngine;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->actionLogger = $actionLogger;
        $this->translator = $translator;
        $this->notificationFactory = $notificationFactory;
    }

    public function sendResettingEmailMessage(User $user): void
    {
        if (! $user->getClient()) {
            $this->sendAdminResettingEmailMessage($user);
        } else {
            $this->sendClientResettingEmailMessage($user);
        }
    }

    private function sendAdminResettingEmailMessage(User $user): void
    {
        $url = $this->publicUrlGenerator->generate(
            'reset_password_do_reset',
            [
                'confirmationToken' => $user->getConfirmationToken(),
            ]
        );

        $message['logMsg'] = [
            'message' => 'Admin has requested a password reset link. It was sent to %s.',
            'replacements' => $user->getEmail(),
        ];

        $this->actionLogger->log(
            $message,
            $user,
            $user->getClient(),
            EntityLog::PASSWORD_CHANGE
        );

        $message = new Message();
        $message->setSubject($this->translator->trans('UCRM password reset'));
        $message->setFrom($this->options->get(Option::MAILER_SENDER_ADDRESS));
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: null);
        $message->setTo($user->getEmail());
        $message->setBody(
            $this->twigEngine->render(
                'email/admin/reset_password.html.twig',
                [
                    'url' => $url,
                ]
            ),
            'text/html'
        );

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_HIGH);
    }

    private function sendClientResettingEmailMessage(User $user): void
    {
        $client = $user->getClient();
        assert($client instanceof Client);

        $notificationTemplate = $this->em->find(
            NotificationTemplate::class,
            NotificationTemplate::CLIENT_FORGOTTEN_PASSWORD
        );

        $url = $this->publicUrlGenerator->generate(
            'reset_password_do_reset',
            [
                'confirmationToken' => $user->getConfirmationToken(),
            ]
        );

        $notification = $this->notificationFactory->create();
        $notification->setClient($client);
        $notification->setClientResetPasswordUrl($url);
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setSubject($notificationTemplate->getSubject());

        $organization = $client->getOrganization();

        $contactEmails = $client->getContactEmails();
        $message = new Message();
        $message->setClient($client);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setTo($contactEmails);
        $message->setBody(
            $this->twigEngine->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        if (! $contactEmails) {
            $this->emailLogger->log(
                $message,
                'Email could not be sent, because client %clientName% (ID: %clientId%) has no email set.',
                EmailLog::STATUS_ERROR,
                ['%clientName%' => $client->getNameForView(), '%clientId%' => $client->getId()]
            );

            throw new NoClientContactException('Email could not be sent, because client has no email set.');
        }

        $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_HIGH);
    }
}
