<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"created"}),
 *     }
 * )
 */
class Download implements LoggableInterface
{
    const STATUS_PENDING = 0;
    const STATUS_READY = 1;
    const STATUS_FAILED = 2;

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_READY => 'Ready',
        self::STATUS_FAILED => 'Failed',
    ];

    const POSSIBLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_READY,
        self::STATUS_FAILED,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name = "download_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable = true)
     */
    protected $path;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(referencedColumnName = "user_id", onDelete = "CASCADE")
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type = "datetime_utc")
     */
    protected $created;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type = "datetime_utc", nullable = true)
     */
    protected $generated;

    /**
     * @var int
     *
     * @ORM\Column(type = "smallint", options = {"unsigned": true})
     * @Assert\Choice(choices=Download::POSSIBLE_STATUSES, strict=true)
     */
    protected $status;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable = true)
     * @Assert\Length(max = "255")
     */
    protected $statusDescription;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setPath(string $path = null)
    {
        $this->path = $path;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime|null
     */
    public function getGenerated()
    {
        return $this->generated;
    }

    /**
     * @param \DateTime|null $generated
     */
    public function setGenerated($generated)
    {
        $this->generated = $generated;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Download %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Download %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return $this->getUser();
    }

    public function getStatusDescription(): ?string
    {
        return $this->statusDescription;
    }

    public function setStatusDescription(?string $statusDescription): void
    {
        $this->statusDescription = $statusDescription;
    }
}
