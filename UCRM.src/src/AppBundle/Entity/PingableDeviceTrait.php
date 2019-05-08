<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait PingableDeviceTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="ping_error_count", type="integer", options={"default":0})
     */
    protected $pingErrorCount = 0;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ping_notification_sent", type="datetime_utc", nullable=true)
     */
    protected $pingNotificationSent;

    /**
     * @var int
     *
     * @ORM\Column(name="ping_notification_sent_status", type="integer", nullable=true)
     */
    protected $pingNotificationSentStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", options={"default":0})
     */
    protected $status = self::STATUS_UNKNOWN;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="ping_notification_user_id", referencedColumnName="user_id", onDelete="SET NULL")
     */
    protected $pingNotificationUser;

    public function getPingErrorCount(): int
    {
        return $this->pingErrorCount;
    }

    /**
     * @return $this
     */
    public function setPingErrorCount(int $pingErrorCount)
    {
        $this->pingErrorCount = $pingErrorCount;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPingNotificationSent()
    {
        return $this->pingNotificationSent;
    }

    /**
     * @return $this
     */
    public function setPingNotificationSent(\DateTime $pingNotificationSent = null)
    {
        $this->pingNotificationSent = $pingNotificationSent;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPingNotificationSentStatus()
    {
        return $this->pingNotificationSentStatus;
    }

    /**
     * @return $this
     */
    public function setPingNotificationSentStatus(int $pingNotificationSentStatus = null)
    {
        $this->pingNotificationSentStatus = $pingNotificationSentStatus;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getPingNotificationUser()
    {
        return $this->pingNotificationUser;
    }

    /**
     * @return $this
     */
    public function setPingNotificationUser(User $pingNotificationUser = null)
    {
        $this->pingNotificationUser = $pingNotificationUser;

        return $this;
    }
}
