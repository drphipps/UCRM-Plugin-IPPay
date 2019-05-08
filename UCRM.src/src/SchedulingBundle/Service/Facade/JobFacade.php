<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Facade;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Download;
use AppBundle\Entity\User;
use AppBundle\Service\DownloadFinisher;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Event\Job\JobAddEvent;
use SchedulingBundle\Event\Job\JobDeleteEvent;
use SchedulingBundle\Event\Job\JobEditEvent;
use SchedulingBundle\FileManager\JobAttachmentFileManager;
use SchedulingBundle\RabbitMq\Job\ExportJobMessage;
use SchedulingBundle\Service\Factory\JobAttachmentFactory;
use SchedulingBundle\Service\Factory\JobCsvFactory;
use SchedulingBundle\Service\Factory\JobPdfFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TransactionEventsBundle\TransactionDispatcher;

class JobFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var JobAttachmentFactory
     */
    private $jobAttachmentFactory;

    /**
     * @var JobAttachmentFileManager
     */
    private $jobAttachmentFileManager;

    /**
     * @var JobCsvFactory
     */
    private $jobCsvFactory;

    /**
     * @var JobPdfFactory
     */
    private $jobPdfFactory;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManagerInterface $em,
        DownloadFinisher $downloadFinisher,
        JobAttachmentFactory $jobAttachmentFactory,
        JobAttachmentFileManager $jobAttachmentFileManager,
        JobCsvFactory $jobCsvFactory,
        JobPdfFactory $jobPdfFactory,
        \Twig_Environment $twig,
        Options $options,
        Pdf $pdf,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->em = $em;
        $this->downloadFinisher = $downloadFinisher;
        $this->jobAttachmentFactory = $jobAttachmentFactory;
        $this->jobAttachmentFileManager = $jobAttachmentFileManager;
        $this->jobCsvFactory = $jobCsvFactory;
        $this->jobPdfFactory = $jobPdfFactory;
        $this->twig = $twig;
        $this->options = $options;
        $this->pdf = $pdf;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function finishCsvExport(int $download, array $jobIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $download,
            'export_jobs.csv',
            function () use ($jobIds) {
                return $this->jobCsvFactory->create($jobIds);
            }
        );
    }

    public function finishPdfExport(int $download, array $jobIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $download,
            'export_jobs.pdf',
            function () use ($jobIds) {
                return $this->jobPdfFactory->create($jobIds);
            }
        );
    }

    public function handleNew(Job $job): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($job) {
                $em->persist($job);
                if ($job->attachmentFiles && is_array($job->attachmentFiles)) {
                    $this->addAttachmentsToJob($job, $job->attachmentFiles);
                }
                yield new JobAddEvent($job);
            }
        );
    }

    public function handleTimelineEdit(array $item, Job $job, Job $jobBeforeEdit): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($item, $job, $jobBeforeEdit) {
                if ($item['start'] ?? false) {
                    try {
                        $start = DateTimeFactory::createFromFormat(\DateTime::RFC3339, $item['start']);
                        $job->setDate($start);
                    } catch (\Exception $exception) {
                        $start = null;
                    }

                    if ($start && array_key_exists('end', $item)) {
                        if ($item['end']) {
                            try {
                                $end = DateTimeFactory::createFromFormat(\DateTime::RFC3339, $item['end']);
                                $duration = (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);
                                $job->setDuration($duration);
                            } catch (\Exception $exception) {
                            }
                        } else {
                            $job->setDuration(null);
                        }
                    }
                }

                $group = array_key_exists('group', $item) ? (int) $item['group'] : null;
                if ($group === 0) {
                    $job->setAssignedUser(null);
                } elseif ($group) {
                    $user = $em->getRepository(User::class)->findOneBy(
                        [
                            'id' => $group,
                            'role' => User::ADMIN_ROLES,
                        ]
                    );
                    if ($user) {
                        $job->setAssignedUser($user);
                    }
                }

                yield new JobEditEvent($job, $jobBeforeEdit);
            }
        );
    }

    public function handleEdit(Job $job, Job $jobBeforeEdit, ?User $user = null): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($job, $jobBeforeEdit, $user) {
                if (
                    $user
                    && ! $jobBeforeEdit->getAssignedUser()
                    && $job->getStatus() !== $jobBeforeEdit->getStatus()
                    && in_array($job->getStatus(), [Job::STATUS_IN_PROGRESS, Job::STATUS_CLOSED], true)
                ) {
                    if (! $job->getAssignedUser()) {
                        $job->setAssignedUser($user);
                    }
                    if (! $job->getDate()) {
                        $job->setDate(new \DateTime());
                    }
                }

                if ($job->attachmentFiles && is_array($job->attachmentFiles)) {
                    $this->addAttachmentsToJob($job, $job->attachmentFiles);
                }
                yield new JobEditEvent($job, $jobBeforeEdit);
            }
        );
    }

    public function handleDelete(Job $job): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $em) use ($job) {
                $em->remove($job);
                yield new JobDeleteEvent($job);
            }
        );
    }

    /**
     * @param Job[]|array $jobs
     *
     * @throws \Throwable
     */
    public function handleDeleteMultiple($jobs): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($jobs) {
                foreach ($jobs as $job) {
                    $entityManager->remove($job);

                    yield new JobDeleteEvent($job);
                }
            }
        );
    }

    public function preparePdfDownload(string $name, array $ids, User $user): void
    {
        $this->prepareDownloads($name, $ids, $user, ExportJobMessage::FORMAT_PDF);
    }

    public function prepareCsvDownload(string $name, array $ids, User $user): void
    {
        $this->prepareDownloads($name, $ids, $user, ExportJobMessage::FORMAT_CSV);
    }

    private function prepareDownloads(string $name, array $ids, User $user, string $filetype): void
    {
        $download = new Download();

        $this->em->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->em->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportJobMessage($download, $ids, $filetype));
    }

    private function addAttachmentsToJob(Job $job, array $attachmentFiles): void
    {
        foreach ($attachmentFiles as $attachmentFile) {
            if ($attachmentFile instanceof UploadedFile) {
                $ticketCommentAttachment = $this->jobAttachmentFactory->createFromUploadedFile($attachmentFile);
                $ticketCommentAttachment->setJob($job);
                $job->getAttachments()->add($ticketCommentAttachment);

                $this->jobAttachmentFileManager->handleAttachmentUpload(
                    $attachmentFile,
                    $ticketCommentAttachment->getFilename()
                );
            }
        }
    }
}
