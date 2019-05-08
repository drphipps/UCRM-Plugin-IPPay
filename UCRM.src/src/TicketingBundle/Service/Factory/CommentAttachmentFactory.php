<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Factory;

use AppBundle\Util\Helpers;
use AppBundle\Util\Strings;
use Ddeboer\Imap\Message\AttachmentInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TicketingBundle\Entity\TicketCommentAttachment;

class CommentAttachmentFactory
{
    public function createFromUploadedFile(UploadedFile $attachmentFile): TicketCommentAttachment
    {
        $ticketCommentAttachment = new TicketCommentAttachment();
        $ticketCommentAttachment->setFilename(Helpers::getUniqueFileName($attachmentFile));
        $ticketCommentAttachment->setMimeType($attachmentFile->getClientMimeType());
        $ticketCommentAttachment->setOriginalFilename(Strings::sanitizeFileName($attachmentFile->getClientOriginalName()));
        $ticketCommentAttachment->setSize($attachmentFile->getClientSize());

        return $ticketCommentAttachment;
    }

    public function createFromFile(File $attachmentFile, string $filename): TicketCommentAttachment
    {
        $ticketCommentAttachment = new TicketCommentAttachment();
        $ticketCommentAttachment->setFilename(Helpers::getUniqueFileName($attachmentFile));
        $ticketCommentAttachment->setMimeType($attachmentFile->getMimeType());
        $ticketCommentAttachment->setOriginalFilename(Strings::sanitizeFileName($filename));
        $ticketCommentAttachment->setSize($attachmentFile->getSize());

        return $ticketCommentAttachment;
    }

    public function createFromMailAttachment(AttachmentInterface $mailAttachment): TicketCommentAttachment
    {
        $ticketCommentAttachment = new TicketCommentAttachment();
        $ticketCommentAttachment->setFilename(Strings::sanitizeFileName($mailAttachment->getFilename()));
        $ticketCommentAttachment->setMimeType($mailAttachment->getType());
        $ticketCommentAttachment->setOriginalFilename(Strings::sanitizeFileName($mailAttachment->getFilename()));
        $ticketCommentAttachment->setSize((int) $mailAttachment->getBytes());
        $ticketCommentAttachment->setPartId($mailAttachment->getPartNumber());

        return $ticketCommentAttachment;
    }
}
