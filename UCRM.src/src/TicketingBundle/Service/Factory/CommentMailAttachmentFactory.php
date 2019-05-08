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
use TicketingBundle\Entity\TicketCommentMailAttachment;

class CommentMailAttachmentFactory
{
    public function createFromMessageAttachment(AttachmentInterface $attachment): TicketCommentMailAttachment
    {
        $ticketCommentAttachment = new TicketCommentMailAttachment();
        $ticketCommentAttachment->setFilename(
            $attachment->getFilename()
                ? Strings::sanitizeFileName($attachment->getFilename())
                : ''
        );
        $ticketCommentAttachment->setMimeType($attachment->getType() ?: '');
        $ticketCommentAttachment->setSize(Helpers::convertImapAttachmentBytes((int) $attachment->getBytes()));
        $ticketCommentAttachment->setPartId($attachment->getPartNumber());

        return $ticketCommentAttachment;
    }
}
