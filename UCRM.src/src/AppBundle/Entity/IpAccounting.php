<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"ip", "date"})
 *     },
 * )
 */
class IpAccounting
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
     * @var int
     *
     * @ORM\Column(type="bigint")
     */
    protected $ip;

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

    public function getIp(): int
    {
        return $this->ip;
    }

    /**
     * @return $this
     */
    public function setIp(int $ip)
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUpload(): int
    {
        return $this->upload;
    }

    /**
     * @return $this
     */
    public function setUpload(int $upload)
    {
        $this->upload = $upload;

        return $this;
    }

    public function getDownload(): int
    {
        return $this->download;
    }

    /**
     * @return $this
     */
    public function setDownload(int $download)
    {
        $this->download = $download;

        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @return $this
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }
}
