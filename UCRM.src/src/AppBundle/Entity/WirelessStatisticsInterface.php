<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface WirelessStatisticsInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return $this
     */
    public function setCcq(int $ccq = null);

    /**
     * @return int|null
     */
    public function getCcq();

    /**
     * @return $this
     */
    public function setRxRate(int $rxRate = null);

    /**
     * @return int|null
     */
    public function getRxRate();

    /**
     * @return $this
     */
    public function setTxRate(int $txRate = null);

    /**
     * @return int|null
     */
    public function getTxRate();

    /**
     * @return $this
     */
    public function setSignal(int $signal = null);

    /**
     * @return int|null
     */
    public function getSignal();

    /**
     * @return $this
     */
    public function setTime(\DateTime $time);

    public function getTime(): \DateTime;
}
