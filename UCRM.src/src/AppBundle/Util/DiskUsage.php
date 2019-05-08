<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Util;

use Symfony\Component\Filesystem\Filesystem;

class DiskUsage
{
    private const UCRM_DISK_USAGE_DIRECTORY = 'UCRM_DISK_USAGE_DIRECTORY';

    public static function get(?string $directory = null): ?array
    {
        $directory = $directory ?? (getenv(self::UCRM_DISK_USAGE_DIRECTORY) ?: '/');
        $fs = new Filesystem();
        if (! $fs->exists($directory)) {
            return null;
        }

        $total = max(disk_total_space($directory), 1);
        $free = disk_free_space($directory);
        $usedPercentage = round(1 - $free / $total, 4) * 100;

        return [
            'used' => $total - $free,
            'total' => $total,
            'usedPercentage' => $usedPercentage,
        ];
    }
}
