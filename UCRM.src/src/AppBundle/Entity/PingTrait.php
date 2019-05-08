<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait PingTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="ping_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="ping", type="float")
     */
    protected $ping;

    /**
     * @var float
     *
     * @ORM\Column(name="packet_loss", type="float")
     */
    protected $packetLoss;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPing(): float
    {
        return $this->ping;
    }

    /**
     * @return $this
     */
    public function setPing(float $ping)
    {
        $this->ping = $ping;

        return $this;
    }

    public function getPacketLoss(): float
    {
        return $this->packetLoss;
    }

    /**
     * @return $this
     */
    public function setPacketLoss(float $packetLoss)
    {
        $this->packetLoss = $packetLoss;

        return $this;
    }
}
