<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface DeviceOutageInterface
{
    public function getOutageStart(): \DateTime;

    /**
     * @return $this
     */
    public function setOutageStart(\DateTime $outageStart);

    /**
     * @return \DateTime|null
     */
    public function getOutageEnd();

    /**
     * @return $this
     */
    public function setOutageEnd(\DateTime $outageEnd = null);

    /**
     * @return int seconds
     */
    public function getDuration(): int;
}
