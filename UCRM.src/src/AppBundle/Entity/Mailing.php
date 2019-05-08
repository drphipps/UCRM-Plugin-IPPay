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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MailingRepository")
 */
class Mailing
{
    /**
     * @var int
     *
     * @ORM\Column(type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime_utc")
     * @Assert\NotBlank()
     */
    protected $createdDate;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterOrganization;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     * @Assert\All({
     *      @Assert\Choice(choices = {Client::TYPE_RESIDENTIAL, Client::TYPE_COMPANY}, strict = true)
     * })
     */
    protected $filterClientType;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterClientTag;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterTariff;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterPeriodStartDay;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterSite;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $filterDevice;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $subject;

    /**
     * @var Collection|EmailLog[]
     *
     * @ORM\OneToMany(targetEntity="EmailLog", mappedBy="bulkMail", cascade={"persist"})
     */
    protected $emailLogs;

    public function __construct()
    {
        $this->emailLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function setFilterOrganizations(?array $organizations): void
    {
        $this->filterOrganization = $organizations;
    }

    public function getFilterOrganizations(): ?array
    {
        return $this->filterOrganization;
    }

    public function setFilterClientType(?array $clientType): void
    {
        $this->filterClientType = $clientType;
    }

    public function getFilterClientType(): ?array
    {
        return $this->filterClientType;
    }

    public function setFilterClientTag(?array $clientTag): void
    {
        $this->filterClientTag = $clientTag;
    }

    public function getFilterClientTag(): ?array
    {
        return $this->filterClientTag;
    }

    public function setFilterServicePlan(?array $tariffs): void
    {
        $this->filterTariff = $tariffs;
    }

    public function getFilterServicePlan(): ?array
    {
        return $this->filterTariff;
    }

    public function setFilterPeriodStartDay(?array $periodStartDay): void
    {
        $this->filterPeriodStartDay = $periodStartDay;
    }

    public function getFilterPeriodStartDay(): ?array
    {
        return $this->filterPeriodStartDay;
    }

    public function setFilterSite(?array $sites): void
    {
        $this->filterSite = $sites;
    }

    public function getFilterSite(): ?array
    {
        return $this->filterSite;
    }

    public function setFilterDevice(?array $devices): void
    {
        $this->filterDevice = $devices;
    }

    public function getFilterDevice(): ?array
    {
        return $this->filterDevice;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function addEmailLog(EmailLog $log): void
    {
        $this->emailLogs[] = $log;
    }

    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }
}
