<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

interface LoggableInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * Get insert message for log.
     *
     * @return array
     */
    public function getLogInsertMessage();

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage();

    /**
     * Get ignored columns for log.
     * Changed columns that are NOT ignored are logged automatically.
     *
     * @return array
     */
    public function getLogIgnoredColumns();

    /**
     * Get Client columns for log.
     *
     * @return Client|null
     */
    public function getLogClient();

    /**
     * Get Site columns for log.
     *
     * @return Site|null
     */
    public function getLogSite();

    /**
     * Get parent entity columns for log.
     *
     * @return object|null
     */
    public function getLogParentEntity();
}
