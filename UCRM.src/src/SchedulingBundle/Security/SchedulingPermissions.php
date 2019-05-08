<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Security;

class SchedulingPermissions
{
    public const JOBS_ALL = 'SchedulingPermissionsVoter::JOBS_ALL';
    public const JOBS_MY = 'SchedulingPermissionsVoter::JOBS_MY';

    public const PERMISSION_SUBJECTS = [
        self::JOBS_ALL,
        self::JOBS_MY,
    ];
}
