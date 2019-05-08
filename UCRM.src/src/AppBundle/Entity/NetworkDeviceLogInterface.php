<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface NetworkDeviceLogInterface
{
    const STATUS_OK = 0;
    const STATUS_ERROR = 1;
    const STATUS_WARNING = 2;

    /**
     * @param \DateTime $createdDate [description]
     */
    public function setCreatedDate(\DateTime $createdDate);

    public function setMessage(string $message = null);

    public function setScript(string $script = null);

    public function setStatus(int $status);
}
