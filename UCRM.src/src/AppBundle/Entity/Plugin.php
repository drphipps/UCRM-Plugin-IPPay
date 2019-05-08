<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PluginRepository")
 */
class Plugin
{
    public const EXECUTION_PERIOD_MINUTE_5 = '5m';
    public const EXECUTION_PERIOD_MINUTE_30 = '30m';
    public const EXECUTION_PERIOD_HOUR_1 = '1h';
    public const EXECUTION_PERIOD_HOUR_6 = '6h';
    public const EXECUTION_PERIOD_HOUR_12 = '12h';
    public const EXECUTION_PERIOD_HOUR_24 = '24h';

    public const EXECUTION_PERIOD_MANUALLY_REQUESTED = 'manually-requested';

    public const EXECUTION_PERIODS = [
        self::EXECUTION_PERIOD_MINUTE_5,
        self::EXECUTION_PERIOD_MINUTE_30,
        self::EXECUTION_PERIOD_HOUR_1,
        self::EXECUTION_PERIOD_HOUR_6,
        self::EXECUTION_PERIOD_HOUR_12,
        self::EXECUTION_PERIOD_HOUR_24,
    ];

    public const EXECUTION_PERIOD_LABELS = [
        self::EXECUTION_PERIOD_MINUTE_5 => '5 minutes',
        self::EXECUTION_PERIOD_MINUTE_30 => '30 minutes',
        self::EXECUTION_PERIOD_HOUR_1 => '1 hour',
        self::EXECUTION_PERIOD_HOUR_6 => '6 hours',
        self::EXECUTION_PERIOD_HOUR_12 => '12 hours',
        self::EXECUTION_PERIOD_HOUR_24 => '24 hours',
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
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $displayName;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull()
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @Assert\NotNull()
     * @Assert\Length(max = 100)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @Assert\NotNull()
     * @Assert\Length(max = 100)
     */
    private $minUcrmVersion;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    private $maxUcrmVersion;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $enabled = false;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Assert\Choice(choices=Plugin::EXECUTION_PERIODS, strict=true)
     */
    private $executionPeriod;

    /**
     * @var AppKey|null
     *
     * @ORM\OneToOne(targetEntity="AppKey", mappedBy="plugin")
     */
    private $appKey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getMinUcrmVersion(): ?string
    {
        return $this->minUcrmVersion;
    }

    public function setMinUcrmVersion(?string $minUcrmVersion): void
    {
        $this->minUcrmVersion = $minUcrmVersion;
    }

    public function getMaxUcrmVersion(): ?string
    {
        return $this->maxUcrmVersion;
    }

    public function setMaxUcrmVersion(?string $maxUcrmVersion): void
    {
        $this->maxUcrmVersion = $maxUcrmVersion;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getExecutionPeriod(): ?string
    {
        return $this->executionPeriod;
    }

    public function setExecutionPeriod(?string $executionPeriod): void
    {
        $this->executionPeriod = $executionPeriod;
    }

    public function getAppKey(): ?AppKey
    {
        return $this->appKey;
    }

    public function setAppKey(?AppKey $appKey): void
    {
        $this->appKey = $appKey;
    }
}
