<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AppKeyRepository")
 */
class AppKey implements LoggableInterface, ParentLoggableInterface, SoftDeleteLoggableInterface
{
    use SoftDeleteableTrait;

    public const TYPE_READ = 'TYPE_READ';
    public const TYPE_WRITE = 'TYPE_WRITE';

    public const POSSIBLE_TYPES = [
        self::TYPE_READ,
        self::TYPE_WRITE,
    ];

    public const TYPE_REPLACE_READABLE = [
        self::TYPE_READ => 'Read',
        self::TYPE_WRITE => 'Write',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="key_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length=256)
     * @Assert\Length(max=256)
     * @Assert\NotNull()
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ORM\Column(length=64, unique=true, nullable=true)
     * @Assert\Length(max=64)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(length=64)
     * @Assert\Choice(choices=AppKey::POSSIBLE_TYPES, strict=true)
     */
    protected $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    protected $lastUsedDate;

    /**
     * @var Plugin|null
     *
     * @ORM\OneToOne(targetEntity="Plugin", inversedBy="appKey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $plugin;

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'App key %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * Get insert message for log.
     *
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'App key %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArchiveMessage()
    {
        $message['logMsg'] = [
            'message' => 'App key %s archived',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogRestoreMessage()
    {
        $message['logMsg'] = [
            'message' => 'App key %s restored',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * Get unloggable column types for log.
     * For example: int, string, choice, password, ...
     *
     * @return array
     */
    public function getLogIgnoredColumns()
    {
        return [
            'key',
        ];
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AppKey
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return AppKey
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return AppKey
     */
    public function setCreatedDate(\DateTime $createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @return AppKey
     */
    public function setLastUsedDate(\DateTime $lastUsedDate = null)
    {
        $this->lastUsedDate = $lastUsedDate;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastUsedDate()
    {
        return $this->lastUsedDate;
    }

    public function getPlugin(): ?Plugin
    {
        return $this->plugin;
    }

    public function setPlugin(?Plugin $plugin): void
    {
        $this->plugin = $plugin;
    }

    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        if ($deletedAt) {
            $this->setKey(null);
        }
    }
}
