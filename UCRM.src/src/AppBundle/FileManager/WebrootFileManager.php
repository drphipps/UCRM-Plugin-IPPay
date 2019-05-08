<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\FileManager;

use AppBundle\Util\Strings;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WebrootFileManager
{
    /**
     * @var string
     */
    private $webrootDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $webrootDir)
    {
        $this->webrootDir = $webrootDir;
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws FileException
     */
    public function handleWebrootUpload(UploadedFile $file): string
    {
        $fileName = Strings::sanitizeFileName($file->getClientOriginalName());

        $file->move($this->webrootDir, $fileName);

        return $fileName;
    }

    public function getFilePathIfExists(string $fileName): ?string
    {
        $fileName = ltrim($fileName, '/');

        // Only return webroot file if the exact path is requested.
        if (! $fileName || Strings::sanitizeFileName($fileName) !== $fileName) {
            return null;
        }

        $path = $this->webrootDir . DIRECTORY_SEPARATOR . Strings::sanitizeFileName($fileName);

        try {
            return $this->filesystem->exists($path) ? $path : null;
        } catch (IOException $exception) {
            return null;
        }
    }

    public function getFiles(): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->webrootDir)
            ->depth('== 0')
            ->ignoreVCS(false)
            ->ignoreDotFiles(false)
            ->sortByName();
        $files = [];

        $timezone = new \DateTimeZone(date_default_timezone_get());
        foreach ($finder as $file) {
            $files[] = [
                'name' => $file->getBasename(),
                'created' => (new \DateTimeImmutable('@' . $file->getCTime()))->setTimezone($timezone),
            ];
        }

        return $files;
    }

    public function deleteFile(string $fileName): void
    {
        $path = $this->getFilePathIfExists($fileName);
        if (! $path) {
            return;
        }

        $this->filesystem->remove($path);
    }
}
