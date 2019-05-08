<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Event\Client\ClientInvitationEmailSentEvent;
use AppBundle\Event\Client\InviteEvent;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Facade\ClientFacade;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Util\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Templating\EngineInterface;
use TransactionEventsBundle\TransactionDispatcher;

class InvitationEmailSender
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @var NotificationFactory
     */
    private $notificationFactory;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        PublicUrlGenerator $publicUrlGenerator,
        Options $options,
        EngineInterface $twigEngine,
        EmailEnqueuer $emailEnqueuer,
        EmailLogger $emailLogger,
        NotificationFactory $notificationFactory,
        ClientFacade $clientFacade,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->publicUrlGenerator = $publicUrlGenerator;
        $this->options = $options;
        $this->twigEngine = $twigEngine;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->emailLogger = $emailLogger;
        $this->notificationFactory = $notificationFactory;
        $this->clientFacade = $clientFacade;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    /**
     * @throws \AppBundle\Exception\NoClientContactException
     */
    public function send(Client $client, int $priority = EmailEnqueuer::PRIORITY_HIGH): void
    {
        $notificationTemplate = $this->entityManager->find(
            NotificationTemplate::class,
            NotificationTemplate::CLIENT_INVITATION
        );
        if (! $client->getUser()->getFirstLoginToken()) {
            $clientBeforeUpdate = clone $client;
            $client->getUser()->setFirstLoginToken(md5($client->getId() . random_bytes(10)));
            $this->clientFacade->handleUpdate($client, $clientBeforeUpdate);
        }

        $url = $this->publicUrlGenerator->generate(
            'first_login_index',
            [
                'id' => $client->getUser()->getId(),
                'firstLoginToken' => $client->getUser()->getFirstLoginToken(),
            ]
        );
        $notification = $this->notificationFactory->create();
        $notification->setClient($client);
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setClientFirstLoginUrl($url);

        $organization = $client->getOrganization();
        $contactEmails = $client->getContactEmails();

        $message = new Message();
        $message->setClient($client);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS, $organization->getEmail()) ?: null);
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

        $this->emailEnqueuer->enqueue(
            $message,
            $priority,
            ClientInvitationEmailSentEvent::class,
            [$client->getId()]
        );

        $this->transactionDispatcher->transactional(
            static function () use ($client) {
                yield new InviteEvent($client);
            }
        );

        $clientBeforeUpdate = clone $client;
        $client->setInvitationEmailSendStatus(Client::INVITATION_EMAIL_SEND_STATUS_PENDING);

        $this->clientFacade->handleUpdate($client, $clientBeforeUpdate);
    }
}
