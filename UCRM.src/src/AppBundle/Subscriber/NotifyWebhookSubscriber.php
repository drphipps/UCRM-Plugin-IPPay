<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber;

use AppBundle\DataProvider\WebhookAddressDataProvider;
use AppBundle\Event\Client\ClientAddEvent;
use AppBundle\Event\Client\ClientArchiveEvent;
use AppBundle\Event\Client\ClientDeleteEvent;
use AppBundle\Event\Client\ClientEditEvent;
use AppBundle\Event\Client\InviteEvent;
use AppBundle\Event\Client\TestEvent;
use AppBundle\Event\Invoice\InvoiceAddDraftEvent;
use AppBundle\Event\Invoice\InvoiceAddEvent;
use AppBundle\Event\Invoice\InvoiceDeleteEvent;
use AppBundle\Event\Invoice\InvoiceDraftApprovedEvent;
use AppBundle\Event\Invoice\InvoiceEditEvent;
use AppBundle\Event\Invoice\InvoiceNearDueEvent;
use AppBundle\Event\Invoice\InvoiceOverdueEvent;
use AppBundle\Event\Payment\PaymentAddEvent;
use AppBundle\Event\Payment\PaymentDeleteEvent;
use AppBundle\Event\Payment\PaymentEditEvent;
use AppBundle\Event\Payment\PaymentUnmatchEvent;
use AppBundle\Event\PaymentPlan\PaymentPlanDeleteEvent;
use AppBundle\Event\PaymentPlan\PaymentPlanEditEvent;
use AppBundle\Event\Quote\QuoteAddEvent;
use AppBundle\Event\Quote\QuoteDeleteEvent;
use AppBundle\Event\Quote\QuoteEditEvent;
use AppBundle\Event\Service\ServiceActivateEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceEndEvent;
use AppBundle\Event\Service\ServicePostponeEvent;
use AppBundle\Event\Service\ServiceSuspendCancelEvent;
use AppBundle\Event\Service\ServiceSuspendEvent;
use AppBundle\Event\User\ResetPasswordEvent;
use AppBundle\Facade\WebhookEventFacade;
use AppBundle\Interfaces\WebhookRequestableInterface;
use AppBundle\RabbitMq\Webhook\WebhookEventRequestMessage;
use Ds\Map;
use Ds\Vector;
use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Event\Job\JobAddEvent;
use SchedulingBundle\Event\Job\JobDeleteEvent;
use SchedulingBundle\Event\Job\JobEditEvent;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\Ticket\TicketDeleteEvent;
use TicketingBundle\Event\Ticket\TicketEditEvent;
use TicketingBundle\Event\Ticket\TicketStatusChangedEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class NotifyWebhookSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var WebhookRequestableInterface[]|Vector
     */
    private $webhookEvents;

    /**
     * @var WebhookAddressDataProvider
     */
    private $webhookAddressDataProvider;

    /**
     * @var WebhookEventFacade
     */
    private $webhookEventFacade;

    /**
     * Entities' previous state (if applicable for the event).
     *
     * @var Map|object[]
     */
    private $webhookPreviousEntities;

    public function __construct(
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        WebhookAddressDataProvider $webhookAddressDataProvider,
        WebhookEventFacade $webhookEventFacade
    ) {
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;

        $this->webhookEvents = new Vector();
        $this->webhookPreviousEntities = new Map();
        $this->webhookAddressDataProvider = $webhookAddressDataProvider;
        $this->webhookEventFacade = $webhookEventFacade;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClientAddEvent::class => 'handleWebhookRequestEvent',
            ClientArchiveEvent::class => 'handleWebhookRequestEvent',
            ClientDeleteEvent::class => 'handleWebhookRequestEvent',
            ClientEditEvent::class => 'handleWebhookRequestEvent',
            InviteEvent::class => 'handleWebhookRequestEvent',
            InvoiceAddDraftEvent::class => 'handleWebhookRequestEvent',
            InvoiceDraftApprovedEvent::class => 'handleWebhookRequestEvent',
            InvoiceAddEvent::class => 'handleWebhookRequestEvent',
            InvoiceDeleteEvent::class => 'handleWebhookRequestEvent',
            InvoiceEditEvent::class => 'handleWebhookRequestEvent',
            InvoiceNearDueEvent::class => 'handleWebhookRequestEvent',
            InvoiceOverdueEvent::class => 'handleWebhookRequestEvent',
            PaymentAddEvent::class => 'handleWebhookRequestEvent',
            PaymentDeleteEvent::class => 'handleWebhookRequestEvent',
            PaymentEditEvent::class => 'handleWebhookRequestEvent',
            PaymentUnmatchEvent::class => 'handleWebhookRequestEvent',
            PaymentPlanDeleteEvent::class => 'handleWebhookRequestEvent',
            PaymentPlanEditEvent::class => 'handleWebhookRequestEvent',
            QuoteAddEvent::class => 'handleWebhookRequestEvent',
            QuoteDeleteEvent::class => 'handleWebhookRequestEvent',
            QuoteEditEvent::class => 'handleWebhookRequestEvent',
            ResetPasswordEvent::class => 'handleWebhookRequestEvent',
            ServiceActivateEvent::class => 'handleWebhookRequestEvent',
            ServiceAddEvent::class => 'handleWebhookRequestEvent',
            ServiceEditEvent::class => 'handleWebhookRequestEvent',
            ServiceArchiveEvent::class => 'handleWebhookRequestEvent',
            ServiceEndEvent::class => 'handleWebhookRequestEvent',
            ServicePostponeEvent::class => 'handleWebhookRequestEvent',
            ServiceSuspendCancelEvent::class => 'handleWebhookRequestEvent',
            ServiceSuspendEvent::class => 'handleWebhookRequestEvent',
            TestEvent::class => 'handleWebhookRequestEvent',
            TicketAddEvent::class => 'handleWebhookRequestEvent',
            // do not add TicketAddImapEvent here - whenever it's yielded, TicketAddEvent is always yielded as well
            TicketCommentAddEvent::class => 'handleWebhookRequestEvent',
            TicketEditEvent::class => 'handleWebhookRequestEvent',
            TicketDeleteEvent::class => 'handleWebhookRequestEvent',
            TicketStatusChangedEvent::class => 'handleWebhookRequestEvent',
            JobAddEvent::class => 'handleWebhookRequestEvent',
            JobEditEvent::class => 'handleWebhookRequestEvent',
            JobDeleteEvent::class => 'handleWebhookRequestEvent',
        ];
    }

    public function handleWebhookRequestEvent(WebhookRequestableInterface $event): void
    {
        $this->webhookEvents->push($event);
    }

    public function preFlush(): void
    {
        // don't bother if no webhooks active
        // do not check this in handleWebhookRequestEvent, otherwise each event will query database
        if (! $this->webhookAddressDataProvider->existsActive()) {
            $this->webhookEvents->clear();
        }

        // previous entities' data will be discarded at commit, so we serialize it here
        foreach ($this->webhookEvents as $event) {
            if ($previousEntity = $event->getWebhookEntityBeforeEdit()) {
                $this->webhookPreviousEntities->put(
                    $event,
                    $this->webhookEventFacade->getDataFromEntity(
                        $event->getWebhookEntityClass(),
                        $event->getWebhookEntityBeforeEdit()
                    )
                );
            }
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->webhookEvents as $event) {
            $previousEntityData = $this->webhookPreviousEntities->hasKey($event)
                ? $this->webhookPreviousEntities->get($event)
                : null;
            $this->rabbitMqEnqueuer->enqueue(
                new WebhookEventRequestMessage(
                    $this->webhookEventFacade->getJsonFromEvent($event, $previousEntityData)
                )
            );
        }
        $this->webhookEvents->clear();
        $this->webhookPreviousEntities->clear();
    }

    public function rollback(): void
    {
        $this->webhookEvents->clear();
        $this->webhookPreviousEntities->clear();
    }
}
