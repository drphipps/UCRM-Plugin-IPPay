<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Report;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Event\Report\ReportGeneratedEvent;
use Ds\Queue;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ReportGeneratedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|User[]
     */
    private $users;

    /**
     * @var Queue|mixed[]
     */
    private $userHeaderNotifications;

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
        TranslatorInterface $translator,
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->translator = $translator;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->users = new Queue();
        $this->userHeaderNotifications = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportGeneratedEvent::class => 'handleNotificationEvent',
        ];
    }

    public function handleNotificationEvent(ReportGeneratedEvent $event): void
    {
        $this->users->push($event->getUser());
    }

    public function preFlush(): void
    {
        foreach ($this->users as $user) {
            $this->userHeaderNotifications->push(
                [
                    'notification' => $this->headerNotificationFactory->create(
                        HeaderNotification::TYPE_INFO,
                        $this->translator->trans('Report is available.'),
                        $this->translator->trans('Report was generated and is available now.')
                    ),
                    'user' => $user,
                ]
            );
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->userHeaderNotifications as $userHeaderNotification) {
            assert($userHeaderNotification['notification'] instanceof HeaderNotification);
            assert($userHeaderNotification['user'] instanceof User);

            $this->headerNotificationSender->sendToAdmin(
                $userHeaderNotification['notification'],
                $userHeaderNotification['user']
            );
        }
    }

    public function rollback(): void
    {
        $this->users->clear();
        $this->userHeaderNotifications->clear();
    }
}
