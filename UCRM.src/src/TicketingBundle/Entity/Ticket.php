<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use AppBundle\Entity\Client;
use AppBundle\Entity\LastSeenTicketComment;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SchedulingBundle\Entity\Job;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="TicketingBundle\Repository\TicketRepository")
 */
class Ticket
{
    public const STATUS_NEW = 0;
    public const STATUS_OPEN = 1;
    public const STATUS_PENDING = 2;
    public const STATUS_SOLVED = 3;

    public const STATUS_NEW_KEY = 'new';
    public const STATUS_OPEN_KEY = 'open';
    public const STATUS_PENDING_KEY = 'pending';
    public const STATUS_SOLVED_KEY = 'solved';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_OPEN => 'Open',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SOLVED => 'Solved',
    ];

    public const STATUS_MAP = [
        self::STATUS_NEW_KEY => self::STATUS_NEW,
        self::STATUS_OPEN_KEY => self::STATUS_OPEN,
        self::STATUS_PENDING_KEY => self::STATUS_PENDING,
        self::STATUS_SOLVED_KEY => self::STATUS_SOLVED,
    ];

    public const STATUSES_NUMERIC = [
        self::STATUS_NEW,
        self::STATUS_OPEN,
        self::STATUS_PENDING,
        self::STATUS_SOLVED,
    ];

    public const STATUS_CLASSES = [
        self::STATUS_NEW => 'new',
        self::STATUS_OPEN => 'open',
        self::STATUS_PENDING => 'pending',
        self::STATUS_SOLVED => 'solved',
    ];

    public const STATUSES_TO_EDIT = [
        self::STATUS_OPEN => self::STATUSES[self::STATUS_OPEN],
        self::STATUS_PENDING => self::STATUSES[self::STATUS_PENDING],
        self::STATUS_SOLVED => self::STATUSES[self::STATUS_SOLVED],
    ];

    public const STATUSES_IS_ACTIVE = [
        self::STATUS_NEW => self::STATUSES[self::STATUS_NEW],
        self::STATUS_OPEN => self::STATUSES[self::STATUS_OPEN],
        self::STATUS_PENDING => self::STATUSES[self::STATUS_PENDING],
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
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $public = true;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $subject;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\Client", inversedBy="tickets")
     * @ORM\JoinColumn(referencedColumnName="client_id", nullable=true, onDelete="CASCADE")
     * @Assert\Expression(
     *     expression="value or this.getEmailFromAddress()",
     *     message="Either clientId or emailFromAddress is required.",
     *     groups={"Api"}
     * )
     */
    private $client;

    /**
     * @var TicketGroup|null
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketGroup", inversedBy="tickets")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $group;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", onDelete="SET NULL")
     */
    private $assignedUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime_utc")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    private $lastActivity;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true, "default": Ticket::STATUS_NEW})
     * @Assert\Choice(choices=Ticket::STATUSES_NUMERIC, strict=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     * @Assert\Email(
     *     strict=true
     * )
     */
    private $emailFromAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $emailFromName;

    /**
     * @var Collection|TicketActivity[]
     *
     * @ORM\OneToMany(targetEntity="TicketActivity", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(referencedColumnName="ticket")
     * @ORM\OrderBy({"id" = "ASC"})
     * @Assert\Count(min=1)
     */
    private $activity;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $isLastActivityByClient = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    private $lastCommentAt;

    /**
     * @var Collection|LastSeenTicketComment[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\LastSeenTicketComment", mappedBy="ticket", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(referencedColumnName="ticket")
     */
    private $lastSeenTicketComments;

    /**
     * @var Collection|Job[]
     *
     * @ORM\ManyToMany(targetEntity="SchedulingBundle\Entity\Job", inversedBy="tickets", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    private $jobs;

    public function __construct()
    {
        $now = new \DateTime();
        $this->activity = new ArrayCollection();
        $this->createdAt = clone $now;
        $this->lastActivity = clone $now;
        $this->lastCommentAt = clone $now;
        $this->status = self::STATUS_NEW;
        $this->lastSeenTicketComments = new ArrayCollection();
        $this->jobs = new ArrayCollection();
    }

    /**
     * Required for detecting changes in TicketActivityLogSubscriber.
     */
    public function __clone()
    {
        $this->jobs = clone $this->jobs;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group): void
    {
        $this->group = $group;
    }

    public function getAssignedUser(): ?User
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?User $assignedUser): void
    {
        $this->assignedUser = $assignedUser;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getLastActivity(): \DateTime
    {
        return $this->lastActivity;
    }

    public function setLastActivity(\DateTime $lastActivity): void
    {
        $this->lastActivity = $lastActivity;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getEmailFromAddress(): ?string
    {
        return $this->emailFromAddress;
    }

    public function setEmailFromAddress($emailFromAddress): void
    {
        $this->emailFromAddress = $emailFromAddress;
    }

    public function getEmailFromName(): ?string
    {
        return $this->emailFromName;
    }

    public function setEmailFromName($emailFromName): void
    {
        $this->emailFromName = $emailFromName;
    }

    public function addActivity(TicketActivity $activity): void
    {
        if ($this->activity->contains($activity)) {
            return;
        }

        $this->activity->add($activity);
    }

    public function removeActivity(TicketActivity $activity): void
    {
        if (! $this->activity->contains($activity)) {
            return;
        }

        $this->activity->removeElement($activity);
    }

    /**
     * @return Collection|TicketActivity[]
     */
    public function getActivity(): Collection
    {
        return $this->activity;
    }

    public function addLastSeenTicketComment(LastSeenTicketComment $lastSeenTicketComment): void
    {
        $this->lastSeenTicketComments->add($lastSeenTicketComment);
    }

    public function removeLastSeenTicketComment(LastSeenTicketComment $lastSeenTicketComment): void
    {
        $this->lastSeenTicketComments->removeElement($lastSeenTicketComment);
    }

    /**
     * @return Collection|LastSeenTicketComment[]
     */
    public function getLastSeenTicketComment(): Collection
    {
        return $this->lastSeenTicketComments;
    }

    public function isLastActivityByClient(): bool
    {
        return $this->isLastActivityByClient;
    }

    public function setIsLastActivityByClient(bool $isLastActivityByClient): void
    {
        $this->isLastActivityByClient = $isLastActivityByClient;
    }

    public function getLastCommentAt(): \DateTime
    {
        return $this->lastCommentAt;
    }

    public function setLastCommentAt(\DateTime $lastCommentAt): void
    {
        $this->lastCommentAt = $lastCommentAt;
    }

    /**
     * @return Collection|Job[]
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    public function addJob(Job $job): void
    {
        if ($this->jobs->contains($job)) {
            return;
        }

        $this->jobs->add($job);
        $job->addTicket($this);
    }

    public function removeJob(Job $job): void
    {
        if (! $this->jobs->contains($job)) {
            return;
        }

        $this->jobs->removeElement($job);
        $job->removeTicket($this);
    }

    /**
     * @return Collection|TicketComment[]
     */
    public function getComments(bool $includePrivate = true): Collection
    {
        // TODO: Use Criteria instead when https://github.com/doctrine/doctrine2/issues/5908 is solved.

        return $this->activity
            ->filter(
                function (TicketActivity $activity) use ($includePrivate) {
                    if (! $includePrivate && ! $activity->isPublic()) {
                        return false;
                    }

                    return $activity instanceof TicketComment;
                }
            );
    }

    public function getClientName(): string
    {
        return (string) ($this->getClient() ? $this->getClient()->getNameForView() : $this->getEmailFromAddress());
    }

    /**
     * @Assert\Callback()
     */
    public function validatePublicComments(ExecutionContextInterface $context): void
    {
        if (! $this->isPublic()) {
            return;
        }

        /** @var TicketComment|null $comment */
        $comment = $this->getComments()->first();
        if ($comment && ! $comment->isPublic()) {
            $context->buildViolation('First ticket comment has to be public.')
                ->atPath('activity')
                ->addViolation();
        }
    }
}
