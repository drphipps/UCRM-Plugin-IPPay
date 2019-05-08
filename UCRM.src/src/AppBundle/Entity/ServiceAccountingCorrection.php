<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceAccountingCorrectionRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"service_id", "date"})
 *     },
 * )
 */
class ServiceAccountingCorrection
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
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(referencedColumnName="service_id", nullable=false, onDelete="CASCADE")
     */
    protected $service;

    /**
     * @var int|string
     *
     * @ORM\Column(type="bigint")
     */
    protected $upload;

    /**
     * @var int|string
     *
     * @ORM\Column(type="bigint")
     */
    protected $download;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    protected $date;

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

    public function getUpload(): int
    {
        return (int) $this->upload;
    }

    public function setUpload(int $upload): void
    {
        $this->upload = $upload;
    }

    public function getDownload(): int
    {
        return (int) $this->download;
    }

    public function setDownload(int $download): void
    {
        $this->download = $download;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = DateTimeFactory::createFromInterface($date);
    }
}
