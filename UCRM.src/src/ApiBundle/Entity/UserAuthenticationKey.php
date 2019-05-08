<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Entity;

use AppBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="ApiBundle\Repository\UserAuthenticationKeyRepository")
 */
class UserAuthenticationKey
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="user_id", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(length=64, unique=true)
     */
    private $key;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     */
    private $createdDate;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $expiration;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $sliding;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     * @Assert\Length(max = 255)
     */
    private $deviceName;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    private $lastUsedDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function setExpiration(int $expiration): void
    {
        $this->expiration = $expiration;
    }

    public function isSliding(): bool
    {
        return $this->sliding;
    }

    public function setSliding(bool $sliding): void
    {
        $this->sliding = $sliding;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function setDeviceName(?string $deviceName): void
    {
        $this->deviceName = $deviceName;
    }

    public function getLastUsedDate(): ?\DateTime
    {
        return $this->lastUsedDate;
    }

    public function setLastUsedDate(?\DateTime $lastUsedDate): void
    {
        $this->lastUsedDate = $lastUsedDate;
    }

    public function isExpired(): bool
    {
        $now = new \DateTime();
        $initialDate = $this->isSliding() && $this->getLastUsedDate()
            ? $this->getLastUsedDate()
            : $this->getCreatedDate();

        $expirationDate = (clone $initialDate)
            ->modify(sprintf('+%d seconds', $this->getExpiration()));

        return $expirationDate < $now;
    }
}
