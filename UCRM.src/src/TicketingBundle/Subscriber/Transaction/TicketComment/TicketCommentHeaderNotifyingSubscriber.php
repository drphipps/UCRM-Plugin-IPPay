<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\TicketComment;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Service\SocketEvent\SocketEventSender;
use Ds\Queue;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Controller\TicketController;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketCommentHeaderNotifyingSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|HeaderNotification[]
     */
    private $headerNotifications;

    /**
     * @var Queue|TicketComment[]
     */
    private $newClientTicketComments;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SocketEventSender
     */
    private $socketEventSender;

    /**
     * @var HeaderNotificationFactory
     */
    private $headerNotificationFactory;

    /**
     * @var HeaderNotificationSender
     */
    private $headerNotificationSender;

    public function __construct(
        Options $options,
        RouterInterface $router,
        TranslatorInterface $translator,
        SocketEventSender $socketEventSender,
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->options = $options;
        $this->router = $router;
        $this->translator = $translator;
        $this->socketEventSender = $socketEventSender;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->headerNotifications = new Queue();
        $this->newClientTicketComments = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketCommentAddEvent::class => 'handleAddTicketCommentEvent',
        ];
    }

    public function handleAddTicketCommentEvent(TicketCommentAddEvent $event): void
    {
        if (
            ! $event->getTicketComment()->getUser()
            && $this->options->get(Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER)
        ) {
            $this->newClientTicketComments->push($event->getTicketComment());
        }
    }

    public function preFlush(): void
    {
        foreach ($this->newClientTicketComments as $ticketComment) {
            $headerNotification = $this->headerNotificationFactory->create(
                HeaderNotification::TYPE_SUCCESS,
                $this->translator->trans('New ticket comment has been created.'),
                $this->translator->trans(
                    'Client %clientName% created new comment of ticket.',
                    [
                        '%clientName%' => $ticketComment->getTicket()->getClientName(),
                    ]
                ),
                $this->router->generate(
                    'ticketing_index',
                    [
                        'ticketId' => $ticketComment->getTicket()->getId(),
                    ]
                )
            );

            $this->headerNotifications->push($headerNotification);
        }
    }

    public function preCommit(): void
    {
        foreach ($this->headerNotifications as $headerNotification) {
            $this->headerNotificationSender->sendByPermission($headerNotification, TicketController::class);
        }
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->newClientTicketComments->clear();
        $this->headerNotifications->clear();
    }
}
