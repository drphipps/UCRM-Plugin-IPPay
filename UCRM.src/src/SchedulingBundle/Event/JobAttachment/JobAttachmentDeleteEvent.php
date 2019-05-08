<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Event\JobAttachment;

use SchedulingBundle\Entity\JobAttachment;
use Symfony\Component\EventDispatcher\Event;

class JobAttachmentDeleteEvent extends Event
{
    /**
     * @var JobAttachment
     */
    protected $jobAttachment;

    public function __construct(JobAttachment $jobAttachment)
    {
        $this->jobAttachment = $jobAttachment;
    }

    public function getJobAttachment(): JobAttachment
    {
        return $this->jobAttachment;
    }
}
