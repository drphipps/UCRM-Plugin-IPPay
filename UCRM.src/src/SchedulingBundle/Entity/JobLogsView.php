<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 */
class JobLogsView
{
    public const LOG_TYPE_ENTITY_LOG = 'entity_log';
    public const LOG_TYPE_JOB_COMMENT = 'job_comment';

    public const LOG_TYPES_ARRAY = [
        self::LOG_TYPE_ENTITY_LOG,
        self::LOG_TYPE_JOB_COMMENT,
    ];

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    private $createdDate;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="Job")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $jobId;

    /**
     * @var int
     *
     * @ORM\Column(name="log_id", type="integer")
     * @ORM\Id()
     */
    private $logId;

    /**
     * @var string
     *
     * @ORM\Column(name="log_type", type="text")
     * @ORM\Id()
     */
    private $logType;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function getJobId(): int
    {
        return $this->jobId;
    }

    public function getLogId(): int
    {
        return $this->logId;
    }

    public function getLogType(): string
    {
        return $this->logType;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
