<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Facade;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;

class JobCommentFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createNewDefault(Job $job, User $user): JobComment
    {
        $comment = new JobComment();
        $comment->setJob($job);
        $comment->setUser($user);

        return $comment;
    }

    public function handleNew(JobComment $comment): void
    {
        $this->em->persist($comment);
        $this->em->flush();
    }

    public function handleEdit(JobComment $comment): void
    {
        $this->em->flush();
    }

    public function handleDelete(JobComment $comment): void
    {
        $this->em->remove($comment);
        $this->em->flush();
    }
}
