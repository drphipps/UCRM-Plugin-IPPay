<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Util\Helpers;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class OrganizationFileManager
{
    /**
     * @var string
     */
    private $logoDir;

    /**
     * @var string
     */
    private $stampDir;

    public function __construct(string $rootDir, Packages $packages)
    {
        $this->logoDir = sprintf(
            '%s/../web/%s',
            $rootDir,
            ltrim($packages->getUrl('', 'logo'), '/')
        );

        $this->stampDir = sprintf(
            '%s/../web/%s',
            $rootDir,
            ltrim($packages->getUrl('', 'stamp'), '/')
        );
    }

    public function deleteLogo(string $logo): void
    {
        $fs = new Filesystem();
        $fs->remove($this->logoDir . '/' . $logo);
    }

    public function deleteStamp(string $stamp): void
    {
        $fs = new Filesystem();
        $fs->remove($this->stampDir . '/' . $stamp);
    }

    public function uploadLogo(File $file): string
    {
        $fileName = Helpers::getUniqueFileName($file);
        $file->move(
            $this->logoDir,
            $fileName
        );

        return $fileName;
    }

    public function uploadStamp(File $file): string
    {
        $fileName = Helpers::getUniqueFileName($file);
        $file->move(
            $this->stampDir,
            $fileName
        );

        return $fileName;
    }
}
