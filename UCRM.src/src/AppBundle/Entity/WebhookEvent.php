<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"created_date"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WebhookEventRepository")
 */
class WebhookEvent
{
    public const ACTIVATE = 'activate';
    public const ARCHIVE = 'archive';
    public const COMMENT = 'comment';
    public const DELETE = 'delete';
    public const DRAFT_APPROVED = 'draft_approved';
    public const EDIT = 'edit';
    public const END = 'end';
    public const INSERT = 'insert';
    public const INVITATION = 'invitation';
    public const NEAR_DUE = 'near_due';
    public const NOTIFICATION = 'notification';
    public const OVERDUE = 'overdue';
    public const POSTPONE = 'postpone';
    public const RESET_PASSWORD = 'reset_password';
    public const STATUS_CHANGE = 'status_change';
    public const SUSPEND = 'suspend';
    public const TEST = 'test';
    public const UNSUSPEND = 'unsuspend';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(type="guid", unique=true)
     */
    protected $uuid;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $changeType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $entity;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(length=255, options={"default":""})
     */
    protected $eventName;

    /**
     * @var mixed[]
     *
     * @ORM\Column(type="json", options={"default": "[]"})
     */
    protected $extraData = [];

    /**
     * @var Collection|WebhookEventRequest[]
     *
     * @ORM\OneToMany(targetEntity="WebhookEventRequest", mappedBy="webHookEvent", cascade={"persist", "remove"})
     * @ORM\OrderBy({"requestDate" = "DESC"})
     */
    protected $requests;

    public function __construct()
    {
        // note: set only at creation - there's no ->setUuid()
        $this->uuid = Uuid::uuid4()->toString();
        $this->requests = new ArrayCollection();
        $this->createdDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getChangeType(): string
    {
        return $this->changeType;
    }

    public function setChangeType(string $changeType): void
    {
        $this->changeType = $changeType;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return Collection|WebhookEventRequest[]
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(WebhookEventRequest $request): void
    {
        $this->requests[] = $request;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    /**
     * @return mixed[]
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param mixed[] $extraData
     */
    public function setExtraData(array $extraData): void
    {
        $this->extraData = $extraData;
    }
}
