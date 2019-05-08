<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Facade;

use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Entity\JobAttachment;
use SchedulingBundle\Event\JobAttachment\JobAttachmentDeleteEvent;
use SchedulingBundle\FileManager\JobAttachmentFileManager;
use Symfony\Component\HttpFoundation\File\File;
use TransactionEventsBundle\TransactionDispatcher;

class JobAttachmentFacade
{
    /**
     * @var EntityManagerInterface
     */
    public $em;

    /**
     * @var JobAttachmentFileManager
     */
    private $jobAttachmentFileManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        JobAttachmentFileManager $jobAttachmentFileManager,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->em = $em;
        $this->jobAttachmentFileManager = $jobAttachmentFileManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function handleDelete(JobAttachment $jobAttachment): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($jobAttachment) {
                $jobAttachment->getJob()->removeAttachment($jobAttachment);
                $entityManager->remove($jobAttachment);

                yield new JobAttachmentDeleteEvent($jobAttachment);
            }
        );
    }

    public function handleEdit(JobAttachment $jobAttachment): void
    {
        $this->em->flush();
    }

    public function handleNew(JobAttachment $jobAttachment, File $attachmentFile): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($jobAttachment, $attachmentFile) {
                $jobAttachment->getJob()->addAttachment($jobAttachment);

                $this->jobAttachmentFileManager->handleAttachmentUpload($attachmentFile, $jobAttachment->getFilename());
            }
        );
    }
}
