<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Subscriber\Transaction\DraftGeneration;

use AppBundle\Entity\DraftGeneration;
use AppBundle\Entity\EntityLog;
use AppBundle\Event\DraftGeneration\DraftGenerationEditEvent;
use AppBundle\Event\Invoice\RecurringInvoicesGeneratedEvent;
use AppBundle\Service\ActionLogger;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Queue;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class DraftGenerationFinishedSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ActionLogger
     */
    private $actionLogger;

    /**
     * @var Queue|DraftGeneration[]
     */
    private $finished;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ActionLogger $actionLogger
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->actionLogger = $actionLogger;
        $this->finished = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DraftGenerationEditEvent::class => 'handleDraftGenerationEditEvent',
        ];
    }

    public function handleDraftGenerationEditEvent(DraftGenerationEditEvent $event): void
    {
        $draftGeneration = $event->getDraftGeneration();
        if ($draftGeneration->getCountSuccess() + $draftGeneration->getCountFailure() >= $draftGeneration->getCount()) {
            $this->finished->push($draftGeneration);

            $approvedDrafts = [];
            $createdDrafts = [];
            foreach ($draftGeneration->getItems() as $item) {
                if ($item->isDraft()) {
                    $createdDrafts[] = $item->getInvoice();
                } else {
                    $approvedDrafts[] = $item->getInvoice();
                }
            }
            $this->eventDispatcher->dispatch(
                RecurringInvoicesGeneratedEvent::class,
                new RecurringInvoicesGeneratedEvent($approvedDrafts, $createdDrafts)
            );
        }
    }

    public function preFlush(): void
    {
        foreach ($this->finished as $draftGeneration) {
            $this->entityManager->persist($this->createGenerationFinishedLog());
            $this->entityManager->remove($draftGeneration);
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
    }

    public function rollback(): void
    {
        $this->finished->clear();
    }

    /**
     * @todo refactor this, ActionLogger service is useless in subscriber as it flushes EntityManager
     */
    private function createGenerationFinishedLog(): EntityLog
    {
        $message['logMsg'] = [
            'message' => 'Recurring invoices generated.',
            'replacements' => '',
        ];
        $log = new EntityLog();
        $log->setCreatedDate(new \DateTime());
        $log->setLog(serialize($message));
        $log->setChangeType(EntityLog::RECURRING_INVOICES);
        $log->setUserType(EntityLog::SYSTEM);

        return $log;
    }
}
