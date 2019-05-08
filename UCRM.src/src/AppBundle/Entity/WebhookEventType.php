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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WebhookEventTypeRepository")
 */
class WebhookEventType
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
     * @ORM\Column(length=255, unique=true)
     * @Assert\NotNull()
     * @Assert\Length(max = 255)
     */
    private $eventName;

    /**
     * @var Collection|WebhookAddress[]
     *
     * @ORM\ManyToMany(targetEntity="WebhookAddress", mappedBy="webhookEventTypes")
     */
    private $webhookAddresses;

    public function __construct()
    {
        $this->webhookAddresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(?string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * @return Collection|WebhookAddress[]
     */
    public function getWebhookAddresses(): Collection
    {
        return $this->webhookAddresses;
    }

    public function addWebhookAddress(WebhookAddress $webhookAddress): void
    {
        if ($this->webhookAddresses->contains($webhookAddress)) {
            return;
        }

        $this->webhookAddresses->add($webhookAddress);
        $webhookAddress->addWebhookEventType($this);
    }

    public function removeWebhookAddress(WebhookAddress $webhookAddress): void
    {
        if (! $this->webhookAddresses->contains($webhookAddress)) {
            return;
        }

        $this->webhookAddresses->removeElement($webhookAddress);
        $webhookAddress->removeWebhookEventType($this);
    }
}
