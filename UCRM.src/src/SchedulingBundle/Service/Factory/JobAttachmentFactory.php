<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Factory;

use AppBundle\Util\Helpers;
use AppBundle\Util\Strings;
use SchedulingBundle\Entity\JobAttachment;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobAttachmentFactory
{
    public function createFromUploadedFile(UploadedFile $attachmentFile): JobAttachment
    {
        $jobAttachment = new JobAttachment();
        $jobAttachment->setFilename(Helpers::getUniqueFileName($attachmentFile));
        $jobAttachment->setMimeType($attachmentFile->getClientMimeType());
        $jobAttachment->setOriginalFilename(Strings::sanitizeFileName($attachmentFile->getClientOriginalName()));
        $jobAttachment->setSize($attachmentFile->getClientSize());

        return $jobAttachment;
    }

    public function createFromFile(File $attachmentFile, string $filename): JobAttachment
    {
        $jobAttachment = new JobAttachment();
        $jobAttachment->setFilename(Helpers::getUniqueFileName($attachmentFile));
        $jobAttachment->setMimeType($attachmentFile->getMimeType());
        $jobAttachment->setOriginalFilename(Strings::sanitizeFileName($filename));
        $jobAttachment->setSize($attachmentFile->getSize());

        return $jobAttachment;
    }
}
