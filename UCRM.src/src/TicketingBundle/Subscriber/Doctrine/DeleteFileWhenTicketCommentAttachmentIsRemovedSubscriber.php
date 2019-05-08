<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use EntitySubscribersBundle\Event\EntityEventSubscriber;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\FileManager\CommentAttachmentFileManager;

class DeleteFileWhenTicketCommentAttachmentIsRemovedSubscriber implements EntityEventSubscriber
{
    /**
     * @var CommentAttachmentFileManager
     */
    private $commentAttachmentFileManager;

    public function __construct(CommentAttachmentFileManager $commentAttachmentFileManager)
    {
        $this->commentAttachmentFileManager = $commentAttachmentFileManager;
    }

    public function subscribesToEntity(LoadClassMetadataEventArgs $event): bool
    {
        return TicketCommentAttachment::class === $event->getClassMetadata()->getName();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(TicketCommentAttachment $ticketCommentAttachment): void
    {
        try {
            (new Filesystem())->remove(
                $this->commentAttachmentFileManager->getFilePath($ticketCommentAttachment)
            );
        } catch (IOException $e) {
            // Silently ignore.
        }
    }
}
