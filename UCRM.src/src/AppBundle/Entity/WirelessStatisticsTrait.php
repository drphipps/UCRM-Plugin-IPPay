<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait WirelessStatisticsTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="ccq", type="integer", nullable=true)
     */
    protected $ccq;

    /**
     * @var int
     *
     * @ORM\Column(name="rx_rate", type="integer", nullable=true)
     */
    protected $rxRate;

    /**
     * @var int
     *
     * @ORM\Column(name="tx_rate", type="integer", nullable=true)
     */
    protected $txRate;

    /**
     * @var int
     *
     * @ORM\Column(name="signal", type="integer", nullable=true)
     */
    protected $signal;

    /**
     * @var int
     *
     * @ORM\Column(name="remote_signal", type="integer", nullable=true)
     */
    protected $remoteSignal;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setCcq(int $ccq = null)
    {
        $this->ccq = $ccq;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCcq()
    {
        return $this->ccq;
    }

    /**
     * @return $this
     */
    public function setRxRate(int $rxRate = null)
    {
        $this->rxRate = $rxRate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRxRate()
    {
        return $this->rxRate;
    }

    /**
     * @return $this
     */
    public function setTxRate(int $txRate = null)
    {
        $this->txRate = $txRate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTxRate()
    {
        return $this->txRate;
    }

    /**
     * @return $this
     */
    public function setSignal(int $signal = null)
    {
        $this->signal = $signal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSignal()
    {
        return $this->signal;
    }

    /**
     * @return $this
     */
    public function setRemoteSignal(int $remoteSignal = null)
    {
        $this->remoteSignal = $remoteSignal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRemoteSignal()
    {
        return $this->remoteSignal;
    }

    /**
     * @return $this
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;

        return $this;
    }

    public function getTime(): \DateTime
    {
        return $this->time;
    }
}
