<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\CsvImport;

use AppBundle\Component\HeaderNotification\Factory\HeaderNotificationFactory;
use AppBundle\Component\HeaderNotification\HeaderNotificationSender;
use AppBundle\Entity\CsvImport;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\User;
use AppBundle\Event\CsvImport\CsvImportEditEvent;
use AppBundle\Service\EntityManagerRecreator;
use Doctrine\ORM\EntityManager;
use Ds\Queue;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

/**
 * @deprecated this subscriber is deprecated and should not be used for new code.
 * We have ImportFinishedSubscriber for refactored imports,
 * this one is used only for payment import, which is not yet refactored
 * @see \AppBundle\Subscriber\Transaction\Import\ImportFinishedSubscriber
 */
class CsvImportFinishedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue|CsvImport[]
     */
    private $finishedCsvImports;

    /**
     * @var Queue|mixed[]
     */
    private $userHeaderNotifications;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerRecreator
     */
    private $entityManagerRecreator;

    /**
     * @var HeaderNotificationFactory
     */
    private $headerNotificationFactory;

    /**
     * @var HeaderNotificationSender
     */
    private $headerNotificationSender;

    public function __construct(
        EntityManager $entityManager,
        EntityManagerRecreator $entityManagerRecreator,
        TranslatorInterface $translator,
        HeaderNotificationFactory $headerNotificationFactory,
        HeaderNotificationSender $headerNotificationSender
    ) {
        $this->entityManager = $entityManager;
        $this->entityManagerRecreator = $entityManagerRecreator;
        $this->translator = $translator;
        $this->headerNotificationFactory = $headerNotificationFactory;
        $this->headerNotificationSender = $headerNotificationSender;

        $this->finishedCsvImports = new Queue();
        $this->userHeaderNotifications = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CsvImportEditEvent::class => 'handleCsvImportEditEvent',
        ];
    }

    public function handleCsvImportEditEvent(CsvImportEditEvent $event): void
    {
        $csvImport = $event->getCsvImport();
        if ($csvImport->getCountSuccess() + $csvImport->getCountFailure() >= $csvImport->getCount()) {
            $this->finishedCsvImports->push($event->getCsvImport());
        }
    }

    public function preFlush(): void
    {
        if (! $this->entityManager->isOpen()) {
            $this->entityManager = $this->entityManagerRecreator->create($this->entityManager);
        }

        foreach ($this->finishedCsvImports as $csvImport) {
            // no need to notify about empty imports, even though this should not ever happen
            // also notify only the user, that started the import, useless to know for others
            if ($csvImport->getCount() <= 0 || ! $csvImport->getUser()) {
                $this->deleteCsvImport($csvImport);

                continue;
            }

            $success = null;
            $failure = null;
            switch ($csvImport->getType()) {
                case CsvImport::TYPE_CLIENT:
                    if ($csvImport->getCountSuccess() > 0) {
                        $success = $this->translator->transChoice(
                            'Successfully imported %count% clients.',
                            $csvImport->getCountSuccess(),
                            [
                                '%count%' => $csvImport->getCountSuccess(),
                            ]
                        );
                    }

                    if ($csvImport->getCountFailure() > 0) {
                        $failure = $this->translator->transChoice(
                            '%count% clients could not be imported because of an error.',
                            $csvImport->getCountFailure(),
                            [
                                '%count%' => $csvImport->getCountFailure(),
                            ]
                        );
                    }
                    break;
                case CsvImport::TYPE_PAYMENT:
                    if ($csvImport->getCountSuccess() > 0) {
                        $success = $this->translator->transChoice(
                            'Successfully imported %count% payments.',
                            $csvImport->getCountSuccess(),
                            [
                                '%count%' => $csvImport->getCountSuccess(),
                            ]
                        );
                    }

                    if ($csvImport->getCountFailure() > 0) {
                        $failure = $this->translator->transChoice(
                            '%count% payments could not be imported because of an error.',
                            $csvImport->getCountFailure(),
                            [
                                '%count%' => $csvImport->getCountFailure(),
                            ]
                        );
                    }
                    break;
            }

            if ($success) {
                $this->userHeaderNotifications->push(
                    [
                        'notification' => $this->headerNotificationFactory->create(
                            HeaderNotification::TYPE_SUCCESS,
                            $this->translator->trans('CSV import finished'),
                            $success
                        ),
                        'user' => $csvImport->getUser(),
                    ]
                );
            }

            if ($failure) {
                $this->userHeaderNotifications->push(
                    [
                        'notification' => $this->headerNotificationFactory->create(
                            HeaderNotification::TYPE_WARNING,
                            $this->translator->trans('There were errors in CSV import'),
                            $failure
                        ),
                        'user' => $csvImport->getUser(),
                    ]
                );
            }

            $this->deleteCsvImport($csvImport);
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
        $this->finishedCsvImports->clear();
        $this->userHeaderNotifications->clear();
    }

    private function deleteCsvImport(CsvImport $csvImport): void
    {
        if (! $this->entityManager->contains($csvImport)) {
            $csvImport = $this->entityManager->find(CsvImport::class, $csvImport->getId());
        }

        if ($csvImport) {
            $this->entityManager->remove($csvImport);
            $this->entityManager->flush($csvImport);
        }
    }
}
