<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\HeaderNotificationRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"created_date"}),
 *     }
 * )
 */
class HeaderNotification
{
    public const TYPE_INFO = 1;
    public const TYPE_SUCCESS = 2;
    public const TYPE_WARNING = 3;
    public const TYPE_DANGER = 4;

    public const TYPES = [
        self::TYPE_INFO => 'info',
        self::TYPE_SUCCESS => 'success',
        self::TYPE_WARNING => 'warning',
        self::TYPE_DANGER => 'danger',
    ];

    public const POSSIBLE_TYPES = [
        self::TYPE_INFO,
        self::TYPE_SUCCESS,
        self::TYPE_WARNING,
        self::TYPE_DANGER,
    ];

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true, "default": HeaderNotification::TYPE_INFO})
     * @Assert\Choice(choices=HeaderNotification::POSSIBLE_TYPES, strict=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(length=255, nullable=true)
     */
    protected $link;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    protected $createdDate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $linkTargetBlank = false;

    public function __construct()
    {
        // @todo use what @janprochazkacz is using when available
        $this->id = Uuid::uuid4()->toString();
        $this->createdDate = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        if (! in_array($type, self::POSSIBLE_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Type "%d" is not supported.', $type));
        }

        $this->type = $type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link = null): void
    {
        $this->link = $link;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): void
    {
        $this->description = $description;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function isLinkTargetBlank(): bool
    {
        return $this->linkTargetBlank;
    }

    public function setLinkTargetBlank(bool $linkTargetBlank): void
    {
        $this->linkTargetBlank = $linkTargetBlank;
    }
}
