<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobAttachment;
use SchedulingBundle\FileManager\JobAttachmentFileManager;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobAttachmentFacade;
use SchedulingBundle\Service\Facade\JobFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/scheduling/job/attachment")
 */
class JobAttachmentController extends BaseController
{
    /**
     * @Route("/{id}", name="scheduling_job_attachment_get", requirements={"id": "\d+"})
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function getJobAttachment(JobAttachment $jobAttachment): BinaryFileResponse
    {
        if ($jobAttachment->getJob()->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $this->get(JobAttachmentFileManager::class)->getAttachmentsDir() . DIRECTORY_SEPARATOR . $jobAttachment->getFilename(),
            $jobAttachment->getOriginalFilename(),
            $jobAttachment->getMimeType()
        );
    }

    /**
     * @Route("/new/{job}", name="scheduling_job_attachment_new", requirements={"job": "\d+"}, options={"expose": true})
     * @Method("POST")
     * @CsrfToken(methods={"POST"})
     * @Permission("guest")
     */
    public function newJobAttachment(Request $request, Job $job): Response
    {
        $this->checkEditPrivileges($job);

        $jobBeforeEdit = clone $job;
        $attachmentFiles = $request->files->get('attachments', '');
        if ($attachmentFiles === '') {
            $this->addTranslatedFlash('error', 'Attachment files can\'t be empty.');

            return $this->createAjaxResponse();
        }

        $job->attachmentFiles = $attachmentFiles;
        if (Helpers::isDemo()) {
            $job->attachmentFiles = [];
            $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
        }

        $this->get(JobFacade::class)->handleEdit($job, $jobBeforeEdit);

        $attachments = [];

        /** @var JobAttachment $attachment */
        foreach ($job->getAttachments() as $attachment) {
            $attachments[] = [
                'id' => $attachment->getId(),
                'original_filename' => $attachment->getOriginalFilename(),
                'size' => Helpers::bytesToSize($attachment->getSize()),
                'urlGet' => $this->generateUrl('scheduling_job_attachment_get', ['id' => $attachment->getId()]),
            ];
        }

        if (! Helpers::isDemo()) {
            $this->addTranslatedFlash('success', 'Item has been saved.');
        }

        return $this->createAjaxResponse(['attachments' => $attachments]);
    }

    /**
     * @Route("/{id}/delete", name="scheduling_job_attachment_delete", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function deleteAction(JobAttachment $attachment): Response
    {
        $this->checkEditPrivileges($attachment->getJob());

        $this->get(JobAttachmentFacade::class)->handleDelete($attachment);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->createAjaxResponse();
    }

    private function checkEditPrivileges(Job $job): void
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
        }
    }
}
