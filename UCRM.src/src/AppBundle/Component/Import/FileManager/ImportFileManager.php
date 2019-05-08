<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\FileManager;

use AppBundle\Entity\Import\ImportInterface;
use AppBundle\Util\Files;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class ImportFileManager
{
    /**
     * @var string
     */
    private $importDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $importDir)
    {
        $this->importDir = $importDir;
        $this->filesystem = new Filesystem();
    }

    public function save(ImportInterface $import, File $file): void
    {
        $file->move($this->importDir, sprintf('%s.csv', $import->getId()));
    }

    public function exists(ImportInterface $import): bool
    {
        return $this->filesystem->exists($this->getPath($import->getId()));
    }

    /**
     * @throws FileNotFoundException
     */
    public function get(ImportInterface $import): \SplFileObject
    {
        return (new File($this->getPath($import->getId())))->openFile();
    }

    /**
     * @throws FileNotFoundException
     */
    public function getHash(ImportInterface $import): string
    {
        return sha1((new File($this->getPath($import->getId())))->openFile()->fgets());
    }

    public function checkEncodingUTF8(ImportInterface $import): bool
    {
        return Files::checkEncoding($this->getPath($import->getId()));
    }

    public function delete(ImportInterface $import): void
    {
        if (! $this->exists($import)) {
            return;
        }

        $this->filesystem->remove($this->getPath($import->getId()));
    }

    private function getPath(string $uuid)
    {
        return sprintf('%s/%s.csv', $this->importDir, $uuid);
    }
}
