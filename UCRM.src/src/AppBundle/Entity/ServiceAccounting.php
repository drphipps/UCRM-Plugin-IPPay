<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Util\Helpers;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceAccountingRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"service_id", "date"})
 *     },
 * )
 */
class ServiceAccounting
{
    /**
     * @var int
     *
     * @ORM\Column(name="accounting_id", type="integer")
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
     * @var int
     *
     * @ORM\Column(type="bigint")
     */
    protected $upload;

    /**
     * @var int
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
        // casting from string (BIGINT)
        return Helpers::typeCast('integer', $this->upload);
    }

    public function getDownload(): int
    {
        // casting from string (BIGINT)
        return Helpers::typeCast('integer', $this->download);
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }
}
