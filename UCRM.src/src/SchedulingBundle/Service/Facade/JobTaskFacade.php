<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Facade;

use SchedulingBundle\Entity\JobTask;
use TransactionEventsBundle\TransactionDispatcher;

class JobTaskFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(TransactionDispatcher $transactionDispatcher)
    {
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleNew(JobTask $task): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($task) {
                $task->getJob()->addTask($task);
            }
        );
    }

    public function handleEdit(JobTask $task): void
    {
        $this->transactionDispatcher->transactional(
            function () {
            }
        );
    }

    public function handleDelete(JobTask $task): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($task) {
                $task->getJob()->removeTask($task);
            }
        );
    }
}
