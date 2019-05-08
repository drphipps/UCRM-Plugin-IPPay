<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Util\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ReportDataUsageRepository")
 */
class ReportDataUsage
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
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(referencedColumnName="service_id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $service;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    private $reportCreated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $currentPeriodStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $currentPeriodEnd;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $currentPeriodDownload;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $currentPeriodUpload;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $lastPeriodStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $lastPeriodEnd;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $lastPeriodDownload;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $lastPeriodUpload;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function setService(Service $service): void
    {
        $this->service = $service;
    }

    public function getReportCreated(): \DateTime
    {
        return $this->reportCreated;
    }

    public function setReportCreated(\DateTime $reportCreated): void
    {
        $this->reportCreated = $reportCreated;
    }

    public function getCurrentPeriodStart(): ?\DateTime
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(?\DateTime $currentPeriodStart): void
    {
        $this->currentPeriodStart = $currentPeriodStart;
    }

    public function getCurrentPeriodEnd(): ?\DateTime
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(?\DateTime $currentPeriodEnd): void
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
    }

    public function getCurrentPeriodDownload(): ?int
    {
        return Helpers::typeCastNullable('integer', $this->currentPeriodDownload);
    }

    public function setCurrentPeriodDownload(?int $currentPeriodDownload): void
    {
        $this->currentPeriodDownload = $currentPeriodDownload;
    }

    public function getCurrentPeriodUpload(): ?int
    {
        return Helpers::typeCastNullable('integer', $this->currentPeriodUpload);
    }

    public function setCurrentPeriodUpload(int $currentPeriodUpload): void
    {
        $this->currentPeriodUpload = $currentPeriodUpload;
    }

    public function getLastPeriodStart(): ?\DateTime
    {
        return $this->lastPeriodStart;
    }

    public function setLastPeriodStart(?\DateTime $lastPeriodStart): void
    {
        $this->lastPeriodStart = $lastPeriodStart;
    }

    public function getLastPeriodEnd(): ?\DateTime
    {
        return $this->lastPeriodEnd;
    }

    public function setLastPeriodEnd(?\DateTime $lastPeriodEnd): void
    {
        $this->lastPeriodEnd = $lastPeriodEnd;
    }

    public function getLastPeriodDownload(): ?int
    {
        return Helpers::typeCastNullable('integer', $this->lastPeriodDownload);
    }

    public function setLastPeriodDownload(?int $lastPeriodDownload): void
    {
        $this->lastPeriodDownload = $lastPeriodDownload;
    }

    public function getLastPeriodUpload(): ?int
    {
        return Helpers::typeCastNullable('integer', $this->lastPeriodUpload);
    }

    public function setLastPeriodUpload(?int $lastPeriodUpload): void
    {
        $this->lastPeriodUpload = $lastPeriodUpload;
    }
}
