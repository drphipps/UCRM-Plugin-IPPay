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
 * @ORM\Table(indexes={@ORM\Index(columns={"url"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WebhookAddressRepository")
 */
class WebhookAddress implements SoftDeleteableInterface
{
    use SoftDeleteableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull()
     * @Assert\Url()
     */
    protected $url;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $isActive = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $anyEvent = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $verifySslCertificate = true;

    /**
     * @var Collection|WebhookEventRequest[]
     *
     * @ORM\OneToMany(targetEntity="WebhookEventRequest", mappedBy="webhookAddress")
     */
    protected $webhookEventRequests;

    /**
     * @var Collection|WebhookEventType[]
     *
     * @ORM\ManyToMany(targetEntity="WebhookEventType", inversedBy="webhookAddresses")
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"eventName" = "ASC"})
     */
    protected $webhookEventTypes;

    public function __construct()
    {
        $this->webhookEventRequests = new ArrayCollection();
        $this->webhookEventTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isAnyEvent(): bool
    {
        return $this->anyEvent;
    }

    public function setAnyEvent(bool $anyEvent): void
    {
        $this->anyEvent = $anyEvent;
    }

    public function isVerifySslCertificate(): bool
    {
        return $this->verifySslCertificate;
    }

    public function setVerifySslCertificate(bool $verifySslCertificate): void
    {
        $this->verifySslCertificate = $verifySslCertificate;
    }

    /**
     * @return Collection|WebhookEventRequest[]
     */
    public function getWebhookEventRequests(): Collection
    {
        return $this->webhookEventRequests;
    }

    public function addWebhookEventRequest(WebhookEventRequest $webhookEventRequest): void
    {
        $this->webhookEventRequests[] = $webhookEventRequest;
    }

    /**
     * @return Collection|WebhookEventType[]
     */
    public function getWebhookEventTypes(): Collection
    {
        return $this->webhookEventTypes;
    }

    public function addWebhookEventType(WebhookEventType $webhookEventType): void
    {
        if ($this->webhookEventTypes->contains($webhookEventType)) {
            return;
        }

        $this->webhookEventTypes->add($webhookEventType);
        $webhookEventType->addWebhookAddress($this);
    }

    public function removeWebhookEventType(WebhookEventType $webhookEventType): void
    {
        if (! $this->webhookEventTypes->contains($webhookEventType)) {
            return;
        }

        $this->webhookEventTypes->removeElement($webhookEventType);
        $webhookEventType->removeWebhookAddress($this);
    }
}
