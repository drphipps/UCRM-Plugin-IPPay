<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Download;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\Download;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Event\Download\DownloadFinishedEvent;
use Ds\Queue;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DownloadFinishedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|HeaderNotification[]
     */
    private $allAdminsHeaderNotifications;

    /**
     * @var Queue|Download[]
     */
    private $downloads;

    /**
     * @var Queue|mixed[]
     */
    private $userHeaderNotifications;

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
        RouterInterface $router,
        TranslatorInterface $translator,
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->router = $router;
        $this->translator = $translator;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->allAdminsHeaderNotifications = new Queue();
        $this->downloads = new Queue();
        $this->userHeaderNotifications = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DownloadFinishedEvent::class => 'handleNotificationEvent',
        ];
    }

    public function handleNotificationEvent(DownloadFinishedEvent $event): void
    {
        if (! $event->getSendNotification()) {
            return;
        }

        $this->downloads->push($event->getDownload());
    }

    public function preFlush(): void
    {
        foreach ($this->downloads as $download) {
            if ($download->getStatus() === Download::STATUS_READY) {
                $notificationStatus = HeaderNotification::TYPE_SUCCESS;
                $notificationTitle = $this->translator->trans('Download is ready.');
                $notificationLink = $this->router->generate(
                    'download_download',
                    [
                        'id' => $download->getId(),
                    ]
                );
            } else {
                $notificationStatus = HeaderNotification::TYPE_WARNING;
                $notificationTitle = $this->translator->trans('Download failed to generate.');
                $notificationLink = null;
            }

            $headerNotification = $this->headerNotificationFactory->create(
                $notificationStatus,
                $notificationTitle,
                $download->getName(),
                $notificationLink
            );

            if ($user = $download->getUser()) {
                if (! $user->isAdmin()) {
                    throw new \InvalidArgumentException('HeaderNotification can be only sent to admin user.');
                }

                $this->userHeaderNotifications->push(
                    [
                        'notification' => $headerNotification,
                        'user' => $user,
                    ]
                );
            } else {
                $this->allAdminsHeaderNotifications->push($headerNotification);
            }
        }
    }

    public function preCommit(): void
    {
        foreach ($this->userHeaderNotifications as $userHeaderNotification) {
            assert($userHeaderNotification['notification'] instanceof HeaderNotification);
            assert($userHeaderNotification['user'] instanceof User);

            $this->headerNotificationSender->sendToAdmin(
                $userHeaderNotification['notification'],
                $userHeaderNotification['user']
            );
        }

        foreach ($this->allAdminsHeaderNotifications as $allAdminsHeaderNotification) {
            $this->headerNotificationSender->sendToAllAdmins($allAdminsHeaderNotification);
        }
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->allAdminsHeaderNotifications->clear();
        $this->downloads->clear();
        $this->userHeaderNotifications->clear();
    }
}
