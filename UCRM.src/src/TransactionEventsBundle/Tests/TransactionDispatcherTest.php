<?php

declare(strict_types=1);

namespace TransactionEventsBundle\Tests;

use AppBundle\Service\EntityManagerRecreator;
use Doctrine\ORM\EntityManager;
use Eloquent\Phony\Phpunit\Phony;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TransactionEventsBundle\TransactionDispatcher;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TransactionDispatcherTest extends TestCase
{
    /**
     * @group TransactionDispatcher
     */
    public function testTransactional(): void
    {
        $eventDispatcherHandle = Phony::mock(EventDispatcherInterface::class);
        $eventDispatcherMock = $eventDispatcherHandle->get();

        $entityManagerHandle = Phony::mock(EntityManager::class);
        $entityManagerHandle->isOpen->returns(true);
        $entityManagerMock = $entityManagerHandle->get();

        $containerHandle = Phony::mock(ContainerInterface::class);
        $containerMock = $containerHandle->get();

        $containerHandle->initialized->with('subscriber1')->returns(false);
        $containerHandle->initialized->with('subscriber2')->returns(true);

        $transactionEventSubscriberHandle = Phony::mock(TransactionEventSubscriberInterface::class);

        $containerHandle->get->with('subscriber2')->returns($transactionEventSubscriberHandle->get());

        $entityManagerRecreatorHandle = Phony::mock(EntityManagerRecreator::class);
        $entityManagerRecreatorMock = $entityManagerRecreatorHandle->get();

        $transactionDispatcher = new TransactionDispatcher(
            ['subscriber1', 'subscriber2'],
            $eventDispatcherMock,
            $entityManagerMock,
            $containerMock,
            $entityManagerRecreatorMock
        );

        $event = new Event();

        $generator = Phony::stub()->with($entityManagerMock)->generates()->yields($event)->returns(42);
        $return = $transactionDispatcher->transactional($generator);

        self::assertSame(42, $return);

        Phony::inOrder(
            $entityManagerHandle->beginTransaction->called(),
            $eventDispatcherHandle->dispatch->calledWith(Event::class, $event),
            $entityManagerHandle->flush->called(),
            $containerHandle->initialized->calledWith('subscriber1'),
            $containerHandle->initialized->calledWith('subscriber2'),
            $transactionEventSubscriberHandle->preFlush->called(),
            $entityManagerHandle->flush->called(),
            $transactionEventSubscriberHandle->preCommit->called(),
            $entityManagerHandle->commit->called(),
            $transactionEventSubscriberHandle->postCommit->called()
        );

        $containerHandle->get->never()->calledWith('subscriber1');
    }

    /**
     * @group TransactionDispatcher
     * @dataProvider dataTransactionalExceptions
     */
    public function testTransactionalExceptions(callable $operation, string $expectedMessage): void
    {
        $eventDispatcherHandle = Phony::mock(EventDispatcherInterface::class);
        $eventDispatcherMock = $eventDispatcherHandle->get();

        $entityManagerHandle = Phony::mock(EntityManager::class);
        $entityManagerHandle->isOpen->returns(true);
        $entityManagerMock = $entityManagerHandle->get();

        $containerHandle = Phony::mock(ContainerInterface::class);
        $containerMock = $containerHandle->get();

        $entityManagerRecreatorHandle = Phony::mock(EntityManagerRecreator::class);
        $entityManagerRecreatorMock = $entityManagerRecreatorHandle->get();

        $transactionDispatcher = new TransactionDispatcher(
            [],
            $eventDispatcherMock,
            $entityManagerMock,
            $containerMock,
            $entityManagerRecreatorMock
        );

        try {
            $transactionDispatcher->transactional($operation);
            self::fail();
        } catch (\InvalidArgumentException $e) {
            self::assertSame($expectedMessage, $e->getMessage());
        }

        $entityManagerHandle->beginTransaction->called();
        $entityManagerHandle->commit->never()->called();
    }

    public function dataTransactionalExceptions(): array
    {
        return [
            [
                'operation' => function () {
                    yield 1;
                },
                'expectedMessage' => 'Generator is expected to yield only events.',
            ],
            [
                'operation' => function () {
                    yield new Event();

                    return new Event();
                },
                'expectedMessage' => 'Events should be yielded, not returned.',
            ],
        ];
    }
}
