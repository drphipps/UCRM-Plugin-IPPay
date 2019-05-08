<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface SoftDeleteLoggableInterface extends LoggableInterface
{
    /**
     * @return array
     */
    public function getLogArchiveMessage();

    /**
     * @return array
     */
    public function getLogRestoreMessage();
}
