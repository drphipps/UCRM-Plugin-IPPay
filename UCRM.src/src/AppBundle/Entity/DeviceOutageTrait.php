<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

trait DeviceOutageTrait
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="outage_start", type="datetime_utc")
     */
    protected $outageStart;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="outage_end", type="datetime_utc", nullable=true)
     */
    protected $outageEnd;

    /**
     * @return DeviceOutageInterface
     */
    public function setOutageStart(\DateTime $outageStart)
    {
        $this->outageStart = $outageStart;

        return $this;
    }

    public function getOutageStart(): \DateTime
    {
        return $this->outageStart;
    }

    /**
     * @return DeviceOutageInterface
     */
    public function setOutageEnd(\DateTime $outageEnd = null)
    {
        $this->outageEnd = $outageEnd;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getOutageEnd()
    {
        return $this->outageEnd;
    }

    /**
     * @return int seconds
     */
    public function getDuration(): int
    {
        $end = $this->getOutageEnd() ?? new \DateTime();

        return $end->getTimestamp() - $this->getOutageStart()->getTimestamp();
    }
}
