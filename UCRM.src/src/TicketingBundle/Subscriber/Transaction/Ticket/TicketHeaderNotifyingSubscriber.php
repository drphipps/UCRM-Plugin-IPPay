<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\Ticket;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Ds\Queue;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TicketingBundle\Controller\TicketController;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketHeaderNotifyingSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|HeaderNotification[]
     */
    private $headerNotifications;

    /**
     * @var Queue|Ticket[]
     */
    private $newClientTickets;

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
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->options = $options;
        $this->router = $router;
        $this->translator = $translator;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->headerNotifications = new Queue();
        $this->newClientTickets = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddEvent::class => 'handleAddTicketEvent',
        ];
    }

    public function handleAddTicketEvent(TicketAddEvent $event): void
    {
        // As clients can't create private tickets, we don't want to send any messages for them.
        // Has to be here, because private ticket with only client and not user can be created via API.
        if (! $event->getTicket()->isPublic()) {
            return;
        }

        if (
            ! $event->getTicket()->getActivity()->current()->getUser()
            && $this->options->get(Option::NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER)
        ) {
            $this->newClientTickets->push($event->getTicket());
        }
    }

    public function preFlush(): void
    {
        foreach ($this->newClientTickets as $ticket) {
            $this->headerNotifications->push(
                $this->headerNotificationFactory->create(
                    HeaderNotification::TYPE_SUCCESS,
                    $this->translator->trans('New ticket has been created.'),
                    $this->translator->trans(
                        'Client %clientName% created new ticket.',
                        [
                            '%clientName%' => $ticket->getClientName(),
                        ]
                    ),
                    $this->router->generate(
                        'ticketing_index',
                        [
                            'ticketId' => $ticket->getId(),
                        ]
                    )
                )
            );
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
        $this->newClientTickets->clear();
        $this->headerNotifications->clear();
    }
}
