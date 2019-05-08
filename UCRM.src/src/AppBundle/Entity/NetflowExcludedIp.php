<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use AppBundle\Util\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @UniqueEntity("ipAddress")
 */
class NetflowExcludedIp
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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint")
     * @Assert\Range(min = 0, max = IpRange::IP_MAX)
     * @Assert\NotBlank()
     */
    protected $ipAddress;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getIpAddress(): int
    {
        return Helpers::typeCast('integer', $this->ipAddress);
    }

    public function setIpAddress(int $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }
}
