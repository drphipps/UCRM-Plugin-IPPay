<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Api\Controller;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\DownloadResponseFactory;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketingBundle\Controller\TicketController as AppTicketController;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\FileManager\CommentAttachmentFileManager;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppTicketController::class)
 */
class TicketCommentAttachmentController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var CommentAttachmentFileManager
     */
    public $commentAttachmentFileManager;

    public function __construct(
        DownloadResponseFactory $downloadResponseFactory,
        CommentAttachmentFileManager $commentAttachmentFileManager
    ) {
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->commentAttachmentFileManager = $commentAttachmentFileManager;
    }

    /**
     * @Get(
     *     "/ticketing/tickets/comments/attachments/{id}/file",
     *     name="ticket_comment_attachment_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @Permission("view")
     *
     * @throws NotFoundHttpException
     */
    public function getAction(TicketCommentAttachment $ticketCommentAttachment): BinaryFileResponse
    {
        return $this->downloadResponseFactory->createFromFile(
            $this->commentAttachmentFileManager->getFilePath($ticketCommentAttachment)
        );
    }
}
