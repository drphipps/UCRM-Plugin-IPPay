<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace SchedulingBundle\Api\Controller;

use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;

trait JobPrivilegesTrait
{
    private function checkPrivileges(Job $job, string $permissionLevel): void
    {
        // Not with App key, but with user login (mobile app)
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted($permissionLevel, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted($permissionLevel, SchedulingPermissions::JOBS_ALL);
        }
    }
}
