<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

use AppBundle\Entity\BaseDevice;
use Symfony\Component\Filesystem\Filesystem;

class File
{
    private $rootDir;

    public function __construct(string $rootDir = '')
    {
        $this->rootDir = $rootDir;
    }

    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function save(string $directory, string $fileName, string $content)
    {
        $fs = new Filesystem();
        $fs->mkdir($directory);
        $fs->dumpFile(sprintf('%s/%s', $directory, $fileName), $content);
    }

    public function getDeviceBackupDirectory(BaseDevice $device): string
    {
        return sprintf(
            '%s/data/backup/%s/%d',
            $this->rootDir,
            $device->getBackupDirectory(),
            $device->getId()
        );
    }
}
