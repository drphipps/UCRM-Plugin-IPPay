<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="TicketingBundle\Repository\TicketImapInboxRepository")
 */
class TicketImapInbox
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
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotNull()
     */
    private $serverName;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", options={"unsigned": true, "default":993}, nullable=true)
     */
    private $serverPort = 993;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     * @Assert\Length(max = 255)
     * @Assert\Email(
     *     strict=true
     * )
     */
    private $emailAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $password;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $isDefault = false;

    /**
     * @var TicketGroup|null
     *
     * @ORM\ManyToOne(targetEntity="TicketingBundle\Entity\TicketGroup", inversedBy="ticketImapInboxes")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $ticketGroup;

    /**
     * @var Collection|TicketComment[]
     *
     * @ORM\OneToMany(targetEntity="TicketingBundle\Entity\TicketComment", mappedBy="inbox")
     */
    private $ticketComments;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime_utc", nullable=true)
     */
    private $importStartDate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $verifySslCertificate = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $enabled = true;

    public function __construct()
    {
        $this->ticketComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function setServerName(?string $serverName): void
    {
        $this->serverName = $serverName;
    }

    public function getServerPort(): ?int
    {
        return $this->serverPort;
    }

    public function setServerPort(?int $serverPort): void
    {
        $this->serverPort = $serverPort;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getTicketGroup(): ?TicketGroup
    {
        return $this->ticketGroup;
    }

    public function setTicketGroup(?TicketGroup $ticketGroup): void
    {
        $this->ticketGroup = $ticketGroup;
    }

    public function addTicketComment(TicketComment $ticketComment): void
    {
        $this->ticketComments[] = $ticketComment;
    }

    public function removeTicketComment(TicketComment $ticketComment): void
    {
        $this->ticketComments->removeElement($ticketComment);
    }

    /**
     * @return Collection|TicketComment[]
     */
    public function getTicketComments(): Collection
    {
        return $this->ticketComments;
    }

    public function getImportStartDate(): ?\DateTime
    {
        return $this->importStartDate;
    }

    public function setImportStartDate(?\DateTime $importStartDate): void
    {
        $this->importStartDate = $importStartDate;
    }

    public function isVerifySslCertificate(): bool
    {
        return $this->verifySslCertificate;
    }

    public function setVerifySslCertificate(bool $verifySslCertificate): void
    {
        $this->verifySslCertificate = $verifySslCertificate;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
