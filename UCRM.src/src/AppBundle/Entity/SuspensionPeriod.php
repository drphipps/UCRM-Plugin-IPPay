<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class SuspensionPeriod
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
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="suspensionPeriods")
     * @ORM\JoinColumn(referencedColumnName="service_id", onDelete="CASCADE")
     */
    private $service;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    private $since;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $until;

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

    public function getSince(): \DateTime
    {
        return $this->since;
    }

    public function setSince(\DateTime $since): void
    {
        $this->since = $since;
    }

    public function getUntil(): ?\DateTime
    {
        return $this->until;
    }

    public function setUntil(?\DateTime $until): void
    {
        $this->until = $until;
    }
}
