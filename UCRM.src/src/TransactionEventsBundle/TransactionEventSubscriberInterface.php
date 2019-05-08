<?php

declare(strict_types=1);

namespace TransactionEventsBundle;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface TransactionEventSubscriberInterface extends EventSubscriberInterface
{
    public function preFlush(): void;

    public function preCommit(): void;

    public function postCommit(): void;

    public function rollback(): void;
}
