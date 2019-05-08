<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true, repositoryClass="AppBundle\Repository\ServiceAccountingViewRepository")
 */
class ServiceAccountingView
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     * @ORM\Id()
     */
    private $type;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="service_id", nullable=false)
     */
    private $service;

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

    public function getType(): int
    {
        return $this->type;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getUpload(): int
    {
        return $this->upload;
    }

    public function getDownload(): int
    {
        return $this->download;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }
}
