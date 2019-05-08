<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class UserPersonalization
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $clientShowClientLog = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $clientShowEmailLog = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $clientShowSystemLog = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $dashboardShowOverview = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $schedulingTimelineShowQueue = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isClientShowClientLog(): bool
    {
        return $this->clientShowClientLog;
    }

    public function setClientShowClientLog(bool $clientShowClientLog): void
    {
        $this->clientShowClientLog = $clientShowClientLog;
    }

    public function isClientShowEmailLog(): bool
    {
        return $this->clientShowEmailLog;
    }

    public function setClientShowEmailLog(bool $clientShowEmailLog): void
    {
        $this->clientShowEmailLog = $clientShowEmailLog;
    }

    public function isClientShowSystemLog(): bool
    {
        return $this->clientShowSystemLog;
    }

    public function setClientShowSystemLog(bool $clientShowSystemLog): void
    {
        $this->clientShowSystemLog = $clientShowSystemLog;
    }

    public function getDashboardShowOverview(): bool
    {
        return $this->dashboardShowOverview;
    }

    public function setDashboardShowOverview(bool $dashboardShowOverview): void
    {
        $this->dashboardShowOverview = $dashboardShowOverview;
    }

    public function getSchedulingTimelineShowQueue(): bool
    {
        return $this->schedulingTimelineShowQueue;
    }

    public function setSchedulingTimelineShowQueue(bool $schedulingTimelineShowQueue): void
    {
        $this->schedulingTimelineShowQueue = $schedulingTimelineShowQueue;
    }
}
