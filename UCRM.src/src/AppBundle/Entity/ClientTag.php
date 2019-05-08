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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class ClientTag
{
    public const COLOR_TRANSPARENT = '';
    public const COLOR_PRIMARY = '#00a0df';
    public const COLOR_DANGER = '#ff6363';
    public const COLOR_WARNING = '#f29400';
    public const COLOR_SUCCESS = '#4caf50';

    public const COLOR_GREY_1 = '#e6e6e6';
    public const COLOR_GREY_2 = '#bababa';
    public const COLOR_GREY_3 = '#878787';
    public const COLOR_GREY_4 = '#4d4d4d';
    public const COLOR_GREY_5 = '#1a1a1a';

    public const COLOR_GREEN_1 = '#e6f6cf';
    public const COLOR_GREEN_2 = '#b7e281';
    public const COLOR_GREEN_3 = '#7dbd36';
    public const COLOR_GREEN_4 = '#409600';
    public const COLOR_GREEN_5 = '#246512';

    public const COLOR_CYAN_1 = '#d8f7f3';
    public const COLOR_CYAN_2 = '#92e1d5';
    public const COLOR_CYAN_3 = '#25beb2';
    public const COLOR_CYAN_4 = '#2f9890';
    public const COLOR_CYAN_5 = '#00665e';

    public const COLOR_BLUE_1 = '#e0f1fb';
    public const COLOR_BLUE_2 = '#a6e0fc';
    public const COLOR_BLUE_3 = '#42a3df';
    public const COLOR_BLUE_4 = '#0070e4';
    public const COLOR_BLUE_5 = '#0050a1';

    public const COLOR_PINK_1 = '#fce5f1';
    public const COLOR_PINK_2 = '#ffc8ea';
    public const COLOR_PINK_3 = '#ff7bc3';
    public const COLOR_PINK_4 = '#dc0083';
    public const COLOR_PINK_5 = '#900052';

    public const COLOR_RED_1 = '#ffee9c';
    public const COLOR_RED_2 = '#fed74a';
    public const COLOR_RED_3 = '#ff7123';
    public const COLOR_RED_4 = '#e30000';
    public const COLOR_RED_5 = '#8e1600';

    public const TEXT_WHITE = '#fff';
    public const TEXT_BLACK = '#000';
    public const TEXT_DARK = '#444';

    public const TEXT_GREY_1 = '#888';
    public const TEXT_GREEN_1 = '#4da400';
    public const TEXT_CYAN_1 = '#45818e';
    public const TEXT_BLUE_1 = '#3d85c6';
    public const TEXT_PINK_1 = '#dc5766';
    public const TEXT_RED_1 = '#b45f06';

    // Used to draw colors table, order is important.
    public const COLORS = [
        self::COLOR_TRANSPARENT => self::TEXT_BLACK,
        self::COLOR_GREY_1 => self::TEXT_GREY_1,
        self::COLOR_GREEN_1 => self::TEXT_GREEN_1,
        self::COLOR_CYAN_1 => self::TEXT_CYAN_1,
        self::COLOR_BLUE_1 => self::TEXT_BLUE_1,
        self::COLOR_PINK_1 => self::TEXT_PINK_1,
        self::COLOR_RED_1 => self::TEXT_RED_1,

        self::COLOR_PRIMARY => self::TEXT_WHITE,
        self::COLOR_GREY_2 => self::TEXT_DARK,
        self::COLOR_GREEN_2 => self::TEXT_DARK,
        self::COLOR_CYAN_2 => self::TEXT_DARK,
        self::COLOR_BLUE_2 => self::TEXT_DARK,
        self::COLOR_PINK_2 => self::TEXT_DARK,
        self::COLOR_RED_2 => self::TEXT_DARK,

        self::COLOR_DANGER => self::TEXT_WHITE,
        self::COLOR_GREY_3 => self::TEXT_WHITE,
        self::COLOR_GREEN_3 => self::TEXT_WHITE,
        self::COLOR_CYAN_3 => self::TEXT_WHITE,
        self::COLOR_BLUE_3 => self::TEXT_WHITE,
        self::COLOR_PINK_3 => self::TEXT_WHITE,
        self::COLOR_RED_3 => self::TEXT_WHITE,

        self::COLOR_WARNING => self::TEXT_WHITE,
        self::COLOR_GREY_4 => self::TEXT_WHITE,
        self::COLOR_GREEN_4 => self::TEXT_WHITE,
        self::COLOR_CYAN_4 => self::TEXT_WHITE,
        self::COLOR_BLUE_4 => self::TEXT_WHITE,
        self::COLOR_PINK_4 => self::TEXT_WHITE,
        self::COLOR_RED_4 => self::TEXT_WHITE,

        self::COLOR_SUCCESS => self::TEXT_WHITE,
        self::COLOR_GREY_5 => self::TEXT_WHITE,
        self::COLOR_GREEN_5 => self::TEXT_WHITE,
        self::COLOR_CYAN_5 => self::TEXT_WHITE,
        self::COLOR_BLUE_5 => self::TEXT_WHITE,
        self::COLOR_PINK_5 => self::TEXT_WHITE,
        self::COLOR_RED_5 => self::TEXT_WHITE,
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
     * @ORM\Column(length=255)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $name;

    /**
     * Color in hex format (#abc123).
     *
     * @var string|null
     *
     * @ORM\Column(length=7, nullable=true)
     * @Assert\Length(max = 7)
     * @Assert\Regex("/^#(?:[0-9a-fA-F]{3}){1,2}$/")
     */
    private $colorBackground;

    /**
     * Color in hex format (#abc123).
     *
     * @var string|null
     *
     * @ORM\Column(length=7, nullable=true)
     * @Assert\Length(max = 7)
     * @Assert\Regex("/^#(?:[0-9a-fA-F]{3}){1,2}$/")
     */
    private $colorText;

    /**
     * @var Collection|Client[]
     *
     * @ORM\ManyToMany(targetEntity="Client", mappedBy="clientTags")
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    private $clients;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
    }

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

    public function getColorBackground(): ?string
    {
        return $this->colorBackground;
    }

    public function setColorBackground(?string $colorBackground): void
    {
        $this->colorBackground = $colorBackground;
    }

    public function getColorText(): ?string
    {
        return $this->colorText;
    }

    public function setColorText(?string $colorText): void
    {
        $this->colorText = $colorText;
    }

    /**
     * @return Collection|Client[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): void
    {
        if ($this->clients->contains($client)) {
            return;
        }

        $this->clients->add($client);
        $client->addClientTag($this);
    }

    public function removeClient(Client $client): void
    {
        if (! $this->clients->contains($client)) {
            return;
        }

        $this->clients->removeElement($client);
        $client->removeClientTag($this);
    }
}
