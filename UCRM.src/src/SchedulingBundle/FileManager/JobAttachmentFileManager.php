<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\FileManager;

use ApiBundle\Exception\InvalidBase64Exception;
use AppBundle\Util\Helpers;
use SchedulingBundle\Api\Map\JobAttachmentMap;
use SchedulingBundle\Entity\JobAttachment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class JobAttachmentFileManager
{
    /**
     * @var string
     */
    private $attachmentsDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(string $attachmentsDir, Filesystem $filesystem)
    {
        $this->attachmentsDir = $attachmentsDir;
        $this->filesystem = $filesystem;
    }

    public function handleAttachmentUpload(File $attachmentFile, string $contentFilename): File
    {
        return $attachmentFile->move(
            $this->attachmentsDir,
            $contentFilename
        );
    }

    public function getAttachmentsDir(): string
    {
        return $this->attachmentsDir;
    }

    public function handleAttachmentDelete(JobAttachment $jobAttachment): void
    {
        $this->filesystem->remove($this->getFilePath($jobAttachment));
    }

    public function createTempFileFromAPI(JobAttachmentMap $jobAttachmentMap): File
    {
        $fileDecoded = base64_decode($jobAttachmentMap->file, true);

        if (! $fileDecoded || base64_encode($fileDecoded) !== $jobAttachmentMap->file) {
            throw new InvalidBase64Exception(422, 'File is not properly encoded in Base64.');
        }

        $tmpFilename = Helpers::getTemporaryFile();

        $this->filesystem->dumpFile($tmpFilename, $fileDecoded);

        return new File($tmpFilename);
    }

    public function getFilePath(JobAttachment $jobAttachment): string
    {
        return $this->attachmentsDir . DIRECTORY_SEPARATOR . $jobAttachment->getFilename();
    }
}
