<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\FileManager;

use ApiBundle\Exception\InvalidBase64Exception;
use AppBundle\Util\Helpers;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use TicketingBundle\Api\Map\TicketCommentAttachmentMap;
use TicketingBundle\Entity\TicketCommentAttachmentInterface;

class CommentAttachmentFileManager
{
    /**
     * @var string
     */
    private $attachmentsDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct($attachmentsDir, Filesystem $filesystem)
    {
        $this->attachmentsDir = $attachmentsDir;
        $this->filesystem = $filesystem;
    }

    public function handleAttachmentUpload(
        File $attachmentFile,
        TicketCommentAttachmentInterface $ticketCommentAttachment
    ): File {
        return $attachmentFile->move(
            $this->getDirPath($ticketCommentAttachment),
            $ticketCommentAttachment->getFilename()
        );
    }

    public function getFilePath(TicketCommentAttachmentInterface $ticketCommentAttachment): string
    {
        return $this->getDirPath($ticketCommentAttachment) . DIRECTORY_SEPARATOR . $ticketCommentAttachment->getFilename();
    }

    private function getDirPath(TicketCommentAttachmentInterface $ticketCommentAttachment): string
    {
        $attachmentSubdir = '';
        foreach ($this->createSubdirNamesFromID($ticketCommentAttachment->getTicketComment()->getId()) as $subdir) {
            $attachmentSubdir .= DIRECTORY_SEPARATOR . $subdir;
        }

        return $this->attachmentsDir . $attachmentSubdir;
    }

    public function createTempFileFromAPI(TicketCommentAttachmentMap $attachmentFile): File
    {
        if (! $attachmentFile->file) {
            throw new InvalidBase64Exception(422, 'File is empty.');
        }

        $fileDecoded = base64_decode($attachmentFile->file, true);

        if (! $fileDecoded || base64_encode($fileDecoded) !== $attachmentFile->file) {
            throw new InvalidBase64Exception(422, 'File is not properly encoded in Base64.');
        }

        return $this->createTempFile($fileDecoded);
    }

    public function createTempFile(string $content): File
    {
        $tmpFilename = Helpers::getTemporaryFile();

        $this->filesystem->dumpFile($tmpFilename, $content);

        return new File($tmpFilename);
    }

    /**
     * Creates directory names array from ID, e.g. from 1234567 makes ['67', '45', '32', '01'].
     * It will be used to create directory tree /67/45/32/01,  so in each directory is max. 99 nodes.
     */
    private function createSubdirNamesFromID(int $id): array
    {
        $id = (string) $id;
        if (strlen($id) % 2 !== 0) {
            $id = '0' . $id;
        }

        return array_reverse(
            str_split($id, 2)
        );
    }
}
