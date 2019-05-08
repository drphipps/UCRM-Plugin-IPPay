<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Version;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Controller\UpdatesController;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Event\Version\VersionCheckerNotificationEvent;
use Ds\Queue;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class VersionCheckerSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|HeaderNotification[]
     */
    private $headerNotifications;

    /**
     * @var Queue|mixed[]
     */
    private $newBuilds;

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

        $this->headerNotifications = new Queue();
        $this->newBuilds = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            VersionCheckerNotificationEvent::class => 'handleNewVersionNotification',
        ];
    }

    public function handleNewVersionNotification(VersionCheckerNotificationEvent $event): void
    {
        $this->newBuilds->push(
            [
                'version' => $event->getVersion(),
                'branch' => $event->getChannel(),
            ]
        );
    }

    public function preFlush(): void
    {
        foreach ($this->newBuilds as $newBuild) {
            assert(array_key_exists('version', $newBuild));
            assert(array_key_exists('branch', $newBuild));

            $this->headerNotifications->push(
                $this->headerNotificationFactory->create(
                    HeaderNotification::TYPE_INFO,
                    $this->translator->trans('New UCRM version is available.'),
                    $this->translator->trans(
                        'UCRM version %version% has been released on %branch% branch.',
                        [
                            '%version%' => $newBuild['version'],
                            '%branch%' => $newBuild['branch'],
                        ]
                    ),
                    $this->router->generate('updates_index')
                )
            );
        }
    }

    public function preCommit(): void
    {
        foreach ($this->headerNotifications as $headerNotification) {
            $this->headerNotificationSender->sendByPermission($headerNotification, UpdatesController::class);
        }
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->headerNotifications->clear();
        $this->newBuilds->clear();
    }
}
