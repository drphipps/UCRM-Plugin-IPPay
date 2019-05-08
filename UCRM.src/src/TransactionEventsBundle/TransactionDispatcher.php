<?php

declare(strict_types=1);

namespace TransactionEventsBundle;

use AppBundle\Service\EntityManagerRecreator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TransactionDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $transactionEventSubscriberServices;

    /**
     * @var EntityManagerRecreator
     */
    private $entityManagerRecreator;

    public function __construct(
        array $transactionEventSubscriberServices,
        EventDispatcherInterface $eventDispatcher,
        EntityManager $entityManager,
        ContainerInterface $container,
        EntityManagerRecreator $entityManagerRecreator
    ) {
        $this->transactionEventSubscriberServices = $transactionEventSubscriberServices;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->entityManagerRecreator = $entityManagerRecreator;
    }

    /**
     * @throws \Throwable
     */
    public function transactional(callable $operation, ?int $transactionIsolationLevel = null)
    {
        if ($transactionIsolationLevel !== null) {
            $oldTransactionIsolationLevel = $this->entityManager->getConnection()->getTransactionIsolation();
            $this->entityManager->getConnection()->setTransactionIsolation($transactionIsolationLevel);
        } else {
            $oldTransactionIsolationLevel = null;
        }

        if (! $this->entityManager->isOpen()) {
            $this->entityManager = $this->entityManagerRecreator->create($this->entityManager);
        }

        $this->entityManager->beginTransaction();

        try {
            $generator = $operation($this->entityManager);

            if ($generator instanceof \Generator) {
                $this->dispatchEvents($generator);
                $return = $generator->getReturn();
            } else {
                $return = $generator;
            }

            if ($return instanceof Event) {
                throw new \InvalidArgumentException('Events should be yielded, not returned.');
            }

            $this->entityManager->flush();

            $subscribers = $this->collectInitializedSubscribers();

            foreach ($subscribers as $subscriber) {
                $subscriber->preFlush();
            }

            $this->entityManager->flush();

            foreach ($subscribers as $subscriber) {
                $subscriber->preCommit();
            }

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->close();
            $this->entityManager->rollback();

            foreach ($subscribers ?? [] as $subscriber) {
                $subscriber->rollback();
            }

            throw $e;
        } finally {
            if ($oldTransactionIsolationLevel !== null) {
                $this->entityManager->getConnection()->setTransactionIsolation($oldTransactionIsolationLevel);
            }
        }

        foreach ($subscribers as $subscriber) {
            $subscriber->postCommit();
        }

        return $return;
    }

    private function dispatchEvents(\Generator $generator): void
    {
        foreach ($generator as $event) {
            if (! $event instanceof Event) {
                throw new \InvalidArgumentException('Generator is expected to yield only events.');
            }

            $this->eventDispatcher->dispatch(get_class($event), $event);
        }
    }

    /**
     * @return TransactionEventSubscriberInterface[]
     */
    private function collectInitializedSubscribers(): array
    {
        $subscribers = [];

        foreach ($this->transactionEventSubscriberServices as $service) {
            if (! $this->container->initialized($service)) {
                continue;
            }

            $subscriber = $this->container->get($service);
            assert($subscriber instanceof TransactionEventSubscriberInterface);
            $subscribers[] = $subscriber;
        }

        return $subscribers;
    }
}
