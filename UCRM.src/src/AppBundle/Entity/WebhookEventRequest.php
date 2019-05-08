<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"request_date"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WebhookEventRequestRepository")
 */
class WebhookEventRequest
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime_utc")
     */
    protected $requestDate;

    /**
     * @ORM\ManyToOne(targetEntity="WebhookEvent", inversedBy="requests")
     * @ORM\JoinColumn(name="webhook_event_id", referencedColumnName="id", onDelete = "CASCADE")
     */
    protected $webHookEvent;

    /**
     * @ORM\ManyToOne(targetEntity="WebhookAddress", inversedBy="webhookEventRequests")
     */
    protected $webhookAddress;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":true})
     */
    protected $verifySslCertificate = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $responseCode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $reasonPhrase;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $duration;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $requestBody;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $responseBody;

    public function __construct()
    {
        $this->requestDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestDate(): \DateTime
    {
        return $this->requestDate;
    }

    public function setRequestDate(\DateTime $requestDate): void
    {
        $this->requestDate = $requestDate;
    }

    public function getWebHookEvent(): WebhookEvent
    {
        return $this->webHookEvent;
    }

    public function setWebHookEvent(WebhookEvent $webHookEvent): void
    {
        $this->webHookEvent = $webHookEvent;
    }

    public function getWebhookAddress(): WebhookAddress
    {
        return $this->webhookAddress;
    }

    public function setWebhookAddress(WebhookAddress $webhookAddress): void
    {
        $this->webhookAddress = $webhookAddress;
    }

    public function getVerifySslCertificate(): bool
    {
        return $this->verifySslCertificate;
    }

    public function setVerifySslCertificate(bool $verifySslCertificate): void
    {
        $this->verifySslCertificate = $verifySslCertificate;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }

    public function setReasonPhrase(string $reasonPhrase): void
    {
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    public function setRequestBody(string $requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function setResponseBody(string $responseBody): void
    {
        $this->responseBody = $responseBody;
    }
}
