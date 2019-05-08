<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true, repositoryClass="AppBundle\Repository\ClientLogsViewRepository")
 */
class ClientLogsView
{
    public const LOG_TYPE_CLIENT_LOG = 'client_log';
    public const LOG_TYPE_EMAIL_LOG = 'email_log';
    public const LOG_TYPE_ENTITY_LOG = 'entity_log';

    public const LOG_TYPES_ARRAY = [
        self::LOG_TYPE_CLIENT_LOG,
        self::LOG_TYPE_EMAIL_LOG,
        self::LOG_TYPE_ENTITY_LOG,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="log_id", type="integer")
     */
    private $logId;

    /**
     * @var string
     *
     * @ORM\Column(name="log_type", type="text")
     */
    private $logType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime_utc")
     */
    private $createdDate;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="client_id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogId(): int
    {
        return $this->logId;
    }

    public function getLogType(): string
    {
        return $this->logType;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
