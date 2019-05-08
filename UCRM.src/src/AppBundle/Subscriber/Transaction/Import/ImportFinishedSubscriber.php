<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\Import;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Entity\User;
use AppBundle\Event\Import\ImportEditEvent;
use Ds\Queue;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class ImportFinishedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|ImportInterface[]
     */
    private $finishedImports;

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

        $this->finishedImports = new Queue();
        $this->userHeaderNotifications = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ImportEditEvent::class => 'handleImportEditEvent',
        ];
    }

    public function handleImportEditEvent(ImportEditEvent $event): void
    {
        if (
            $event->getImportBeforeUpdate()->getStatus() !== $event->getImport()->getStatus()
            && $event->getImport()->getStatus() === ImportInterface::STATUS_FINISHED
        ) {
            $this->finishedImports->push($event->getImport());
        }
    }

    public function preFlush(): void
    {
        foreach ($this->finishedImports as $import) {
            // notify only the user, that started the import, useless to know for others
            if (! $import->getUser()) {
                continue;
            }

            switch (true) {
                case $import instanceof ClientImport:
                    $this->enqueueClientImportNotifications($import);
                    break;
                // @todo payments
            }
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

    private function enqueueClientImportNotifications(ClientImport $import): void
    {
        if ($import->getCountSuccess() > 0) {
            $this->userHeaderNotifications->push(
                [
                    'notification' => $this->headerNotificationFactory->create(
                        HeaderNotification::TYPE_SUCCESS,
                        $this->translator->trans('CSV import finished'),
                        $this->translator->transChoice(
                            'Successfully imported %count% clients.',
                            $import->getCountSuccess(),
                            [
                                '%count%' => $import->getCountSuccess(),
                            ]
                        )
                    ),
                    'user' => $import->getUser(),
                ]
            );
        }

        if ($import->getCountFailure() > 0) {
            $this->userHeaderNotifications->push(
                [
                    'notification' => $this->headerNotificationFactory->create(
                        HeaderNotification::TYPE_WARNING,
                        $this->translator->trans('There were errors in CSV import'),
                        $this->translator->transChoice(
                            '%count% clients could not be imported because of an error.',
                            $import->getCountFailure(),
                            [
                                '%count%' => $import->getCountFailure(),
                            ]
                        )
                    ),
                    'user' => $import->getUser(),
                ]
            );
        }
    }

    public function rollback(): void
    {
        $this->finishedImports->clear();
        $this->userHeaderNotifications->clear();
    }
}
