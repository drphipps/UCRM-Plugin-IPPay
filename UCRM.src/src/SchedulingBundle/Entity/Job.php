<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Entity;

use AppBundle\Entity\Client;
use AppBundle\Entity\LoggableInterface;
use AppBundle\Entity\ParentLoggableInterface;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\Ticket;

/**
 * @ORM\Entity(repositoryClass="SchedulingBundle\Repository\JobRepository")
 * @Assert\GroupSequence({"IsUploadedFile", "Job"})
 */
class Job implements LoggableInterface, ParentLoggableInterface
{
    public const STATUS_OPEN = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_CLOSED = 2;

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const STATUSES_NUMERIC = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_CLOSED,
    ];

    public const STATUS_CLASSES = [
        self::STATUS_OPEN => 'open',
        self::STATUS_IN_PROGRESS => 'in-progress',
        self::STATUS_CLOSED => 'closed',
    ];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="guid", unique=true)
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=true, onDelete="SET NULL")
     * @Assert\Expression(expression="not this.getDate() or (this.getDate() and value)", message="You must assign an user in order to set the date.")
     */
    private $assignedUser;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Client")
     * @ORM\JoinColumn(referencedColumnName="client_id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     * @Assert\Expression(expression="not this.getAssignedUser() or (this.getAssignedUser() and value)", message="You must set a date in order to assign user.")
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThanOrEqual(value = 0)
     */
    private $duration = 60;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true, "default": Job::STATUS_OPEN})
     * @Assert\Choice(callback="getValidStatuses", strict=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $address;

    /**
     * @var string|null
     *
     * @ORM\Column(length=50, nullable=true)
     * @Assert\Length(max = 50)
     * @Assert\Range(
     *     min = -90,
     *     max = 90
     * )
     */
    private $gpsLat;

    /**
     * @var string|null
     *
     * @ORM\Column(length=50, nullable=true)
     * @Assert\Length(max = 50)
     * @Assert\Range(
     *     min = -180,
     *     max = 180
     * )
     */
    private $gpsLon;

    /**
     * @var Collection|JobComment[]
     *
     * @ORM\OneToMany(targetEntity="JobComment", mappedBy="job", cascade={"remove", "persist"})
     * @ORM\JoinColumn(referencedColumnName="job")
     */
    private $comments;

    /**
     * @var Collection|JobTask[]
     *
     * @ORM\OneToMany(targetEntity="JobTask", mappedBy="job", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"sequence" = "ASC"})
     * @Assert\Valid()
     */
    private $tasks;

    /**
     * @var Collection|JobAttachment[]
     *
     * @ORM\OneToMany(targetEntity="SchedulingBundle\Entity\JobAttachment", mappedBy="job", cascade={"persist"}, orphanRemoval=true)
     */
    private $attachments;

    /**
     * @var array
     *
     * @Assert\All({
     *     @Assert\Type(
     *          type="\Symfony\Component\HttpFoundation\File\UploadedFile",
     *          groups={"IsUploadedFile"},
     *          message="Uploaded file is not valid."
     *      )
     * })
     */
    public $attachmentFiles;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $public = false;

    /**
     * @var Collection|Ticket[]
     *
     * @ORM\ManyToMany(targetEntity="TicketingBundle\Entity\Ticket", mappedBy="jobs", cascade={"persist"})
     */
    private $tickets;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->status = self::STATUS_OPEN;
        $this->comments = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->uuid = Uuid::uuid4()->toString();
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAssignedUser(): ?User
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?User $assignedUser): void
    {
        $this->assignedUser = $assignedUser;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDateEnd(): ?\DateTime
    {
        if (! $this->date || ! $this->duration) {
            return null;
        }

        return (clone $this->date)->modify(sprintf('+%d minutes', $this->duration));
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getGpsLat(): ?string
    {
        return $this->gpsLat;
    }

    public function setGpsLat(?string $gpsLat): void
    {
        $this->gpsLat = $gpsLat;
    }

    public function getGpsLon(): ?string
    {
        return $this->gpsLon;
    }

    public function setGpsLon(?string $gpsLon): void
    {
        $this->gpsLon = $gpsLon;
    }

    public function addComment(JobComment $comment): void
    {
        $this->comments->add($comment);
    }

    public function removeComment(JobComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * @return Collection|JobComment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addTask(JobTask $task): void
    {
        $task->setJob($this);
        $this->tasks->add($task);
    }

    public function removeTask(JobTask $task): void
    {
        $this->tasks->removeElement($task);
    }

    /**
     * @return Collection|JobTask[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getLogIgnoredColumns(): array
    {
        return [];
    }

    public function getLogClient(): ?Client
    {
        return $this->client;
    }

    public function getLogSite()
    {
        return null;
    }

    public function getLogParentEntity()
    {
        return null;
    }

    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getTitle(),
            'entity' => self::class,
        ];

        return $message;
    }

    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Job %s deleted',
            'replacements' => $this->getTitle(),
        ];

        return $message;
    }

    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Job %s added',
            'replacements' => $this->getTitle(),
        ];

        return $message;
    }

    public static function getValidStatuses(): array
    {
        return array_keys(self::STATUSES);
    }

    /**
     * @return Collection|JobAttachment[]
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(JobAttachment $element): void
    {
        $this->attachments->add($element);
    }

    public function removeAttachment(JobAttachment $element): void
    {
        $this->attachments->removeElement($element);
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): void
    {
        if ($this->tickets->contains($ticket)) {
            return;
        }

        $this->tickets->add($ticket);
        $ticket->addJob($this);
    }

    public function removeTicket(Ticket $ticket): void
    {
        if (! $this->tickets->contains($ticket)) {
            return;
        }

        $this->tickets->removeElement($ticket);
        $ticket->removeJob($this);
    }
}
