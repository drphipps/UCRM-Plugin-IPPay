<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Exception\OptionNotValidException;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Options;
use AppBundle\Util\Message;
use AppBundle\Util\Notification;
use Doctrine\ORM\EntityManager;

class NotificationEmailMessageFactory
{
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
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        EntityManager $em,
        NotificationFactory $notificationFactory,
        Options $options,
        \Twig_Environment $twig
    ) {
        $this->em = $em;
        $this->notificationFactory = $notificationFactory;
        $this->options = $options;
        $this->twig = $twig;
    }

    /**
     * @throws OptionNotValidException
     */
    public function createAdminDraftCreated(array $createdDrafts): Message
    {
        return $this->prepareMessageToAdmin(
            $this->prepareNotification(NotificationTemplate::ADMIN_DRAFT_CREATED, $createdDrafts)
        );
    }

    /**
     * @throws OptionNotValidException
     */
    public function createAdminInvoiceCreated($invoices): Message
    {
        return $this->prepareMessageToAdmin(
            $this->prepareNotification(NotificationTemplate::ADMIN_INVOICE_CREATED, $invoices)
        );
    }

    private function prepareNotification(int $template, array $invoices): Notification
    {
        $notificationTemplate = $this->em->getRepository(NotificationTemplate::class)->find($template);
        $notification = $this->notificationFactory->create();
        $notification->addReplacement('%CREATED_COUNT%', (string) count($invoices));
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());

        $notification->addReplacement(
            '%CREATED_LIST%',
            $this->twig->render(
                'email/admin/invoices.html.twig',
                [
                    'invoices' => $invoices,
                ]
            )
        );

        return $notification;
    }

    /**
     * @throws OptionNotValidException
     */
    private function prepareMessageToAdmin(Notification $notification): Message
    {
        if (! filter_var($this->options->get(Option::MAILER_SENDER_ADDRESS), FILTER_VALIDATE_EMAIL)) {
            throw new OptionNotValidException(
                sprintf('Email address %s is not in valid format', $this->options->get(Option::MAILER_SENDER_ADDRESS))
            );
        }
        if (! filter_var($this->options->get(Option::NOTIFICATION_EMAIL_ADDRESS), FILTER_VALIDATE_EMAIL)) {
            throw new OptionNotValidException(
                sprintf('Email address %s is not in valid format', $this->options->get(Option::NOTIFICATION_EMAIL_ADDRESS))
            );
        }
        $message = new Message();
        $message->setSubject($notification->getSubject());
        $message->setFrom($this->options->get(Option::MAILER_SENDER_ADDRESS));
        $message->setSender($this->options->get(Option::MAILER_SENDER_ADDRESS) ?: null);
        $message->setTo($this->options->get(Option::NOTIFICATION_EMAIL_ADDRESS));
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
}
