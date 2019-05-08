<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Invoice;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Controller\InvoiceController;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Event\Invoice\RecurringInvoicesGeneratedEvent;
use AppBundle\Exception\OptionNotValidException;
use AppBundle\Factory\NotificationEmailMessageFactory;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Options;
use Ds\Queue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class RecurringInvoicesGeneratedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|HeaderNotification[]
     */
    private $headerNotifications;

    /**
     * @var Queue|Invoice[][]
     */
    private $approvedDrafts;

    /**
     * @var Queue|Invoice[][]
     */
    private $createdDrafts;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationEmailMessageFactory
     */
    private $notificationEmailMessageFactory;

    /**
     * @var Options
     */
    private $options;

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
        EmailEnqueuer $emailEnqueuer,
        LoggerInterface $logger,
        NotificationEmailMessageFactory $notificationEmailMessageFactory,
        Options $options,
        TranslatorInterface $translator,
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->emailEnqueuer = $emailEnqueuer;
        $this->logger = $logger;
        $this->notificationEmailMessageFactory = $notificationEmailMessageFactory;
        $this->options = $options;
        $this->translator = $translator;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->headerNotifications = new Queue();
        $this->approvedDrafts = new Queue();
        $this->createdDrafts = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RecurringInvoicesGeneratedEvent::class => 'handleNotifications',
        ];
    }

    public function handleNotifications(RecurringInvoicesGeneratedEvent $event)
    {
        $this->approvedDrafts->push($event->getApprovedDrafts());
        $this->createdDrafts->push($event->getCreatedDrafts());
    }

    public function preFlush(): void
    {
        foreach ($this->createdDrafts as $createdDrafts) {
            if (count($createdDrafts) === 0) {
                continue;
            }

            if ($this->options->get(Option::NOTIFICATION_CREATED_DRAFTS_BY_EMAIL)) {
                try {
                    $message = $this->notificationEmailMessageFactory->createAdminDraftCreated($createdDrafts);
                    $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
                    $this->logger->info('Admin notification email added to queue. (drafts created)');
                } catch (OptionNotValidException $exception) {
                    $this->logger->error(
                        'Adding admin notification email to queue failed. (' . $exception->getMessage() . ')'
                    );
                }
            }

            if ($this->options->get(Option::NOTIFICATION_CREATED_DRAFTS_IN_HEADER)) {
                $this->headerNotifications->push(
                    $this->headerNotificationFactory->create(
                        HeaderNotification::TYPE_SUCCESS,
                        $this->translator->trans('Invoice drafts are generated.')
                    )
                );
            }
        }

        foreach ($this->approvedDrafts as $approvedDrafts) {
            if (count($approvedDrafts) === 0) {
                continue;
            }

            if ($this->options->get(Option::NOTIFICATION_CREATED_INVOICES_BY_EMAIL)) {
                try {
                    $message = $this->notificationEmailMessageFactory->createAdminInvoiceCreated($approvedDrafts);
                    $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
                    $this->logger->info('Admin notification email added to queue. (invoices created)');
                } catch (OptionNotValidException $exception) {
                    $this->logger->error(
                        'Adding admin notification email to queue failed. (' . $exception->getMessage() . ')'
                    );
                }
            }

            if ($this->options->get(Option::NOTIFICATION_CREATED_INVOICES_IN_HEADER)) {
                $this->headerNotifications->push(
                    $this->headerNotificationFactory->create(
                        HeaderNotification::TYPE_SUCCESS,
                        $this->translator->trans('Invoices are generated.')
                    )
                );
            }
        }
    }

    public function preCommit(): void
    {
        foreach ($this->headerNotifications as $headerNotification) {
            $this->headerNotificationSender->sendByPermission($headerNotification, InvoiceController::class);
        }
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->headerNotifications->clear();
        $this->approvedDrafts->clear();
        $this->createdDrafts->clear();
    }
}
