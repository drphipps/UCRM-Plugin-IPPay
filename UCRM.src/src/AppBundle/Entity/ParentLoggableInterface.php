<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface ParentLoggableInterface
{
    /**
     * Get update message for log.
     *
     * @return array
     */
    public function getLogUpdateMessage();
}
