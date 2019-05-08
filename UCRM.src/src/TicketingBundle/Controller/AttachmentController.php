<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Exception\ImapConnectionException;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\DownloadResponseFactory;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketingBundle\Entity\TicketCommentAttachment;
use TicketingBundle\Entity\TicketCommentMailAttachment;
use TicketingBundle\FileManager\CommentAttachmentFileManager;
use TicketingBundle\Service\Facade\TicketCommentFacade;
use TicketingBundle\Service\Facade\TicketMailFacade;

/**
 * @Route("/ticketing")
 * @PermissionControllerName(TicketController::class)
 */
class AttachmentController extends BaseController
{
    /**
     * @Route("/attachment/{id}", name="ticketing_comment_attachment_get", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function getCommentAttachment(TicketCommentAttachment $ticketCommentAttachment): Response
    {
        $downloadResponseFactory = $this->get(DownloadResponseFactory::class);

        try {
            return  $downloadResponseFactory->createFromFile(
                $this->get(CommentAttachmentFileManager::class)->getFilePath($ticketCommentAttachment),
                $ticketCommentAttachment->getOriginalFilename(),
                $ticketCommentAttachment->getMimeType()
            );
        } catch (NotFoundHttpException $notFoundHttpException) {
            try {
                return $downloadResponseFactory->createFromContent(
                    $this->get(TicketMailFacade::class)->getAttachment($ticketCommentAttachment),
                    $ticketCommentAttachment->getFilename()
                );
            } catch (\RuntimeException  $runtimeException) {
                throw $notFoundHttpException;
            }
        }
    }

    /**
     * @Route("/attachment/{id}/delete", name="ticketing_comment_attachment_delete", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("edit")
     * @CsrfToken()
     */
    public function deleteCommentAttachment(TicketCommentAttachment $ticketCommentAttachment): Response
    {
        $ticketId = $ticketCommentAttachment->getTicketComment()->getTicket()->getId();

        $this->get(TicketCommentFacade::class)->handleDeleteAttachment($ticketCommentAttachment);

        $this->addTranslatedFlash('success', 'Attachment has been deleted.');

        return $this->redirectToRoute(
            'ticketing_index',
            [
                'ticketId' => $ticketId,
            ]
        );
    }

    /**
     * @Route("/mail-attachment/{id}", name="ticketing_comment_mail_attachment_get", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function getCommentMailAttachment(TicketCommentMailAttachment $ticketCommentMailAttachment): Response
    {
        try {
            if (file_exists($this->get(CommentAttachmentFileManager::class)->getFilePath($ticketCommentMailAttachment))) {
                return $this->get(DownloadResponseFactory::class)->createFromFile(
                    $this->get(CommentAttachmentFileManager::class)->getFilePath($ticketCommentMailAttachment),
                    $ticketCommentMailAttachment->getFilename(),
                    $ticketCommentMailAttachment->getMimeType()
                );
            }

            return $this->get(DownloadResponseFactory::class)->createFromContent(
                $this->get(TicketMailFacade::class)->getAttachment($ticketCommentMailAttachment),
                $ticketCommentMailAttachment->getFilename()
            );
        } catch (ImapConnectionException | AuthenticationFailedException $exception) {
            $this->addTranslatedFlash(
                'danger',
                $this->trans('Connection failed.') . ' ' . $exception->getMessage()
            );
        } catch (\Exception $exception) {
            $this->addTranslatedFlash('error', 'Attachment is not available.');
        }

        return $this->redirectToRoute(
            'ticketing_index',
            [
                'ticketId' => $ticketCommentMailAttachment->getTicketComment()->getTicket()->getId(),
            ]
        );
    }
}
