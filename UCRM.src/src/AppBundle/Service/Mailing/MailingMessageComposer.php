<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Mailing;

use AppBundle\Entity\Client;
use AppBundle\Entity\Mailing;
use AppBundle\Entity\Option;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Util\Message;

class MailingMessageComposer
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var NotificationFactory
     */
    private $notificationFactory;

    public function __construct(
        Options $options,
        \Twig_Environment $twig,
        NotificationFactory $notificationFactory
    ) {
        $this->options = $options;
        $this->twig = $twig;
        $this->notificationFactory = $notificationFactory;
    }

    public function composeMail(Client $client, Mailing $mailing, string $subject, string $body): Message
    {
        $organization = $client->getOrganization();
        $contactEmails = $client->getContactEmails();

        $notification = $this->notificationFactory->create();
        $notification->setBodyTemplate($body);
        $notification->setSubject($subject);
        $notification->setClient($client);

        $message = new Message();
        $message->setClient($client);
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: $organization->getEmail() ?: null);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setTo($contactEmails);
        $message->setBody(
            $this->twig->render(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );
        $message->setMailing($mailing);

        return $message;
    }
}
