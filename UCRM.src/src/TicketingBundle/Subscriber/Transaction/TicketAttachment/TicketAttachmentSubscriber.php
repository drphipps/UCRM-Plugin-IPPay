<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Subscriber\Transaction\TicketAttachment;

use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Util\UnitConverter\BinaryConverter;
use Ddeboer\Imap\Message\AttachmentInterface;
use Ds\Queue;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TicketingBundle\Api\Map\TicketCommentAttachmentMap;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Event\Ticket\TicketAddEvent;
use TicketingBundle\Event\TicketComment\TicketCommentAddEvent;
use TicketingBundle\FileManager\CommentAttachmentFileManager;
use TicketingBundle\Service\Factory\CommentAttachmentFactory;
use TransactionEventsBundle\TransactionEventSubscriberInterface;

class TicketAttachmentSubscriber implements TransactionEventSubscriberInterface
{
    /**
     * @var Queue
     */
    private $attachmentsQueue;

    /**
     * @var Queue
     */
    private $attachmentFilesToMoveQueue;

    /**
     * @var CommentAttachmentFactory
     */
    private $commentAttachmentFactory;

    /**
     * @var CommentAttachmentFileManager
     */
    private $commentAttachmentFileManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        CommentAttachmentFactory $commentAttachmentFactory,
        CommentAttachmentFileManager $commentAttachmentFileManager,
        LoggerInterface $logger,
        Options $options
    ) {
        $this->commentAttachmentFactory = $commentAttachmentFactory;
        $this->commentAttachmentFileManager = $commentAttachmentFileManager;
        $this->logger = $logger;
        $this->options = $options;

        $this->attachmentsQueue = new Queue();
        $this->attachmentFilesToMoveQueue = new Queue();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TicketAddEvent::class => 'handleTicketAddEvent',
            TicketCommentAddEvent::class => 'handleTicketCommentAddEvent',
        ];
    }

    public function handleTicketCommentAddEvent(TicketCommentAddEvent $event): void
    {
        $this->attachmentsQueue->push(
            [
                'attachmentFiles' => $event->getAttachmentFiles(),
                'ticketComment' => $event->getTicketComment(),
            ]
        );
    }

    public function handleTicketAddEvent(TicketAddEvent $event): void
    {
        $this->attachmentsQueue->push(
            [
                'attachmentFiles' => $event->getAttachmentFiles(),
                'ticketComment' => $event->getTicketComment(),
            ]
        );
    }

    public function preFlush(): void
    {
        foreach ($this->attachmentsQueue as $attachment) {
            foreach ($attachment['attachmentFiles'] as $attachmentFile) {
                $ticketCommentAttachment = false;
                // From form
                if ($attachmentFile instanceof UploadedFile) {
                    $ticketCommentAttachment = $this->commentAttachmentFactory->createFromUploadedFile($attachmentFile);
                }

                // From API
                if ($attachmentFile instanceof TicketCommentAttachmentMap) {
                    $filename = $attachmentFile->filename;
                    $attachmentFile = $this->commentAttachmentFileManager->createTempFileFromAPI($attachmentFile);
                    $ticketCommentAttachment = $this->commentAttachmentFactory->createFromFile($attachmentFile, $filename);
                }

                // From IMAP
                if ($attachmentFile instanceof AttachmentInterface) {
                    $limit = new BinaryConverter(
                        $this->options->get(Option::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT),
                        BinaryConverter::UNIT_MEBI
                    );

                    if ($limit->to(BinaryConverter::UNIT_BYTE) >= $attachmentFile->getBytes()) {
                        if (! $attachmentFile->getFilename() || ! $attachmentFile->getBytes()) {
                            $this->logger->info('Attachment is not imported because it is not valid.');
                            continue;
                        }
                        $ticketCommentAttachment = $this->commentAttachmentFactory->createFromMailAttachment(
                            $attachmentFile
                        );
                        $attachmentFile = $this->commentAttachmentFileManager->createTempFile(
                            $attachmentFile->getDecodedContent()
                        );
                    }
                }

                if ($ticketCommentAttachment) {
                    /** @var TicketComment $ticketComment */
                    $ticketComment = $attachment['ticketComment'];
                    $ticketCommentAttachment->setTicketComment($ticketComment);
                    $ticketComment->getAttachments()->add($ticketCommentAttachment);
                    $this->attachmentFilesToMoveQueue->push(
                        [
                            'attachmentFile' => $attachmentFile,
                            'ticketCommentAttachment' => $ticketCommentAttachment,
                        ]
                    );
                }
            }
        }
    }

    public function preCommit(): void
    {
    }

    public function postCommit(): void
    {
        foreach ($this->attachmentFilesToMoveQueue as $attachmentFile) {
            $this->commentAttachmentFileManager->handleAttachmentUpload(
                $attachmentFile['attachmentFile'],
                $attachmentFile['ticketCommentAttachment']
            );
        }
    }

    public function rollback(): void
    {
        $this->attachmentsQueue->clear();
        $this->attachmentFilesToMoveQueue->clear();
    }
}
