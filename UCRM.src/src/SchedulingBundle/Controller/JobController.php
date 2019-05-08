<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\Client;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use AppBundle\Util\Helpers;
use AppBundle\Util\Map;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;
use SchedulingBundle\Entity\JobLogsView;
use SchedulingBundle\Form\Type\JobCommentType;
use SchedulingBundle\Form\Type\JobType;
use SchedulingBundle\Grid\JobLogsView\JobLogsViewGridFactory;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobCommentFacade;
use SchedulingBundle\Service\Facade\JobFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/scheduling/job")
 */
class JobController extends BaseController
{
    /**
     * @Route("/{id}", name="scheduling_job_show", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showAction(Request $request, Job $job): Response
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
            $editPermissionGranted = $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
            $statusEditPermissionGranted = true;
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
            $editPermissionGranted = $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
            $statusEditPermissionGranted = $editPermissionGranted;
        }

        $logTypeFiltersQuery = $request->get('logType', JobLogsView::LOG_TYPES_ARRAY);
        $jobLogsViewGrid = $this->get(JobLogsViewGridFactory::class)->create(
            $job,
            [
                'logType' => $logTypeFiltersQuery,
            ]
        );
        if ($parameters = $jobLogsViewGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            '@scheduling/job/show.html.twig',
            [
                'job' => $job,
                'editPermissionGranted' => $editPermissionGranted,
                'jobLogsViewGrid' => $jobLogsViewGrid,
                'jobLogsViewFiltersLink' => $this->createJobLogsFiltersLinks($logTypeFiltersQuery),
                'jobLogsViewTypeFilters' => $logTypeFiltersQuery,
                'statusEditPermissionGranted' => $statusEditPermissionGranted,
            ]
        );
    }

    /**
     * @Route("/comment/edit/{id}", name="scheduling_job_comment_edit", requirements={"id": "\d+"})
     * @Method({"GET","POST"})
     * @Permission("guest")
     */
    public function editJobCommentAction(Request $request, JobComment $jobComment): Response
    {
        if (! $this->isSpecialPermissionGranted(SpecialPermission::JOB_COMMENT_EDIT)) {
            throw $this->createAccessDeniedException();
        }

        $job = $jobComment->getJob();
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        }

        $urlParams = ['id' => $jobComment->getId()];
        if ($logTypeFilters = $request->get('logType')) {
            $urlParams['logType'] = $logTypeFilters;
        }
        $url = $this->generateUrl('scheduling_job_comment_edit', $urlParams);
        $form = $this->createForm(JobCommentType::class, $jobComment, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(JobCommentFacade::class)->handleEdit($jobComment);

            $logTypeFiltersQuery = $request->get('logType', JobLogsView::LOG_TYPES_ARRAY);

            $this->invalidateTemplate(
                'job-comments',
                '@scheduling/job/components/view/job_logs.html.twig',
                [
                    'job' => $job,
                    'jobLogsViewGrid' => $this->get(JobLogsViewGridFactory::class)->create($job),
                    'jobLogsViewFiltersLink' => $this->createJobLogsFiltersLinks($logTypeFiltersQuery),
                    'jobLogsViewTypeFilters' => $logTypeFiltersQuery,
                    'showTab' => 'tab-client-log',
                ]
            );

            $this->addTranslatedFlash('success', 'Job comment has been edited.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/components/edit/client_log.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => true,
            ]
        );
    }

    /**
     * @Route("/comment/delete/{id}", name="scheduling_job_comment_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function deleteJobCommentAction(Request $request, JobComment $jobComment): Response
    {
        if (! $this->isSpecialPermissionGranted(SpecialPermission::JOB_COMMENT_EDIT)) {
            throw $this->createAccessDeniedException();
        }

        $job = $jobComment->getJob();
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        }

        $this->get(JobCommentFacade::class)->handleDelete($jobComment);

        $logTypeFiltersQuery = $request->get('logType', JobLogsView::LOG_TYPES_ARRAY);

        $this->invalidateTemplate(
            'job-comments',
            '@scheduling/job/components/view/job_logs.html.twig',
            [
                'job' => $job,
                'jobLogsViewGrid' => $this->get(JobLogsViewGridFactory::class)->create(
                    $job,
                    [
                        'logType' => $logTypeFiltersQuery,
                    ]
                ),
                'jobLogsViewFiltersLink' => $this->createJobLogsFiltersLinks($logTypeFiltersQuery),
                'jobLogsViewTypeFilters' => $logTypeFiltersQuery,
                'showTab' => 'tab-client-log',
            ]
        );

        $this->addTranslatedFlash('success', 'Job comment has been deleted.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{id}/popup", name="scheduling_job_show_popup", requirements={"id": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @Permission("guest")
     */
    public function showPopupAction(Job $job): Response
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
            $editPermissionGranted = $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
            $statusEditPermissionGranted = true;
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
            $editPermissionGranted = $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
            $statusEditPermissionGranted = $editPermissionGranted;
        }

        return $this->render(
            '@scheduling/job/show_popup.html.twig',
            [
                'job' => $job,
                'editPermissionGranted' => $editPermissionGranted,
                'statusEditPermissionGranted' => $statusEditPermissionGranted,
            ]
        );
    }

    /**
     * @Route(
     *     "/new/{client}",
     *     name="scheduling_job_new",
     *     requirements={
     *         "client": "\d+"
     *     }
     * )
     * @ParamConverter("client", options={"id" = "client"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function newAction(Request $request, ?Client $client = null): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);

        return $this->handleNewEditAction($request, null, $client);
    }

    /**
     * @Route("/{id}/edit", name="scheduling_job_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function editAction(Request $request, Job $job): Response
    {
        $this->checkCanEditDelete($job);

        return $this->handleNewEditAction($request, $job, null);
    }

    /**
     * @Route("/{id}/delete", name="scheduling_job_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function deleteAction(Job $job): Response
    {
        $this->checkCanEditDelete($job);
        $this->get(JobFacade::class)->handleDelete($job);
        $this->addTranslatedFlash('success', 'Job has been deleted.');

        return $this->redirectToRoute('scheduling_timeline_index');
    }

    /**
     * @Route(
     *     "/{id}/popup-delete",
     *     name="scheduling_job_popup_delete",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function popupDeleteAction(Job $job): Response
    {
        $this->checkCanEditDelete($job);
        $id = $job->getId();
        $this->get(JobFacade::class)->handleDelete($job);

        return $this->createAjaxResponse(
            [
                'type' => 'delete',
                'id' => $id,
            ]
        );
    }

    /**
     * @Route("/{id}/comments/new", name="scheduling_job_comment_new", requirements={"id": "\d+"})
     * @Method({"GET","POST"})
     * @Permission("guest")
     */
    public function newCommentAction(Request $request, Job $job): Response
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        }

        $commentFacade = $this->get(JobCommentFacade::class);
        $comment = $commentFacade->createNewDefault($job, $this->getUser());

        $urlParams = ['id' => $job->getId()];
        if ($logTypeFilters = $request->get('logType')) {
            $urlParams['logType'] = $logTypeFilters;
        }

        $url = $this->generateUrl('scheduling_job_comment_new', $urlParams);

        $form = $this->createForm(JobCommentType::class, $comment, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentFacade->handleNew($comment);
            $this->em->refresh($job);

            $logTypeFiltersQuery = $request->get('logType', JobLogsView::LOG_TYPES_ARRAY);

            $this->invalidateTemplate(
                'job-comments',
                '@scheduling/job/components/view/job_logs.html.twig',
                [
                    'job' => $job,
                    'jobLogsViewGrid' => $this->get(JobLogsViewGridFactory::class)->create(
                        $job,
                        ['logType' => $logTypeFiltersQuery]
                    ),
                    'jobLogsViewFiltersLink' => $this->createJobLogsFiltersLinks($logTypeFiltersQuery),
                    'jobLogsViewTypeFilters' => $logTypeFiltersQuery,
                ]
            );

            $this->addTranslatedFlash('success', 'Comment has been created.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            '@scheduling/job/components/edit/comment.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/status-edit/{status}",
     *     name="scheduling_job_status_edit",
     *     requirements={"id": "\d+", "status": "\d+"}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function statusEditAction(Job $job, int $status, Request $request): Response
    {
        [$editPermissionGranted, $statusEditPermissionGranted] = $this->checkCanEditStatus($job);
        $jobBeforeEdit = clone $job;

        if (array_key_exists($status, Job::STATUSES)) {
            $job->setStatus($status);
            $this->get(JobFacade::class)->handleEdit($job, $jobBeforeEdit, $this->getUser());
        }

        $this->invalidateTemplate(
            'job-detail',
            '@scheduling/job/components/view/detail.html.twig',
            [
                'job' => $job,
                'editPermissionGranted' => $editPermissionGranted,
                'statusEditPermissionGranted' => $statusEditPermissionGranted,
            ]
        );

        $logTypeFiltersQuery = $request->get('logType', JobLogsView::LOG_TYPES_ARRAY);

        $this->invalidateTemplate(
            'job-comments',
            '@scheduling/job/components/view/job_logs.html.twig',
            [
                'job' => $job,
                'jobLogsViewGrid' => $this->get(JobLogsViewGridFactory::class)->create(
                    $job,
                    ['logType' => $logTypeFiltersQuery]
                ),
                'jobLogsViewFiltersLink' => $this->createJobLogsFiltersLinks($logTypeFiltersQuery),
                'jobLogsViewTypeFilters' => $logTypeFiltersQuery,
                'showTab' => 'tab-client-log',
            ]
        );

        $this->addTranslatedFlash('success', 'Status has been updated.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route(
     *     "/{id}/popup-status-edit/{status}",
     *     name="scheduling_job_popup_status_edit",
     *     requirements={"id": "\d+", "status": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function popupStatusEditAction(Job $job, int $status): Response
    {
        [$editPermissionGranted, $statusEditPermissionGranted] = $this->checkCanEditStatus($job);

        $jobBeforeEdit = clone $job;
        if (array_key_exists($status, Job::STATUSES)) {
            $job->setStatus($status);
            $this->get(JobFacade::class)->handleEdit($job, $jobBeforeEdit);
        }

        $this->invalidateTemplate(
            'timeline-popup__container',
            '@scheduling/job/components/view/popup.html.twig',
            [
                'job' => $job,
                'editPermissionGranted' => $editPermissionGranted,
                'statusEditPermissionGranted' => $statusEditPermissionGranted,
            ]
        );

        return $this->createAjaxResponse(
            [
                'type' => 'status-edit',
                'id' => $job->getId(),
                'className' => sprintf('status--%s', Job::STATUS_CLASSES[$job->getStatus()]),
            ]
        );
    }

    /**
     * Used to populate address input in new/edit job form.
     *
     * @Route(
     *     "/{id}/get-client-address",
     *     name="scheduling_job_get_client_address",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("guest"))
     */
    public function getClientAddressAction(Client $client): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);

        return new JsonResponse(
            [
                'address' => $client->getAddressString(),
            ]
        );
    }

    private function createJobLogsFiltersLinks(array $logTypeFiltersQuery): array
    {
        $clientLogFiltersLink = [];
        foreach (JobLogsView::LOG_TYPES_ARRAY as $identifier) {
            $filter = array_fill_keys($logTypeFiltersQuery, true);
            if (array_key_exists($identifier, $filter)) {
                unset($filter[$identifier]);
            } else {
                $filter[$identifier] = true;
            }
            $clientLogFiltersLink[$identifier] = array_keys($filter);
        }

        return $clientLogFiltersLink;
    }

    private function checkCanEditStatus(Job $job): array
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
            $editPermissionGranted = $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
            $statusEditPermissionGranted = true;
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
            $editPermissionGranted = $statusEditPermissionGranted = true;
        }

        return [$editPermissionGranted, $statusEditPermissionGranted];
    }

    private function checkCanEditDelete(Job $job): void
    {
        if ($job->getAssignedUser() === $this->getUser()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);
        } else {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL);
        }
    }

    private function handleNewEditAction(Request $request, ?Job $job, ?Client $client): Response
    {
        $job = $job ?? new Job();
        $jobBeforeEdit = clone $job;
        $isEdit = (bool) $job->getId();

        $form = $this->createForm(
            JobType::class,
            $job,
            [
                'assignable_user' => $this->isGranted(Permission::EDIT, SchedulingPermissions::JOBS_ALL)
                    ? null
                    : $this->getUser(),
            ]
        );
        if (! $isEdit && $client) {
            $form->get('client')->setData($client);
            $form->get('address')->setData($client->getAddressString());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($job->attachmentFiles && Helpers::isDemo()) {
                $job->attachmentFiles = [];
                $this->addTranslatedFlash('error', 'File upload is is not available in the demo.');
            }

            if ($isEdit) {
                $this->get(JobFacade::class)->handleEdit($job, $jobBeforeEdit, $this->getUser());
                $this->addTranslatedFlash('success', 'Job has been saved.');
            } else {
                $this->get(JobFacade::class)->handleNew($job);
                $this->addTranslatedFlash('success', 'Job has been added.');
            }

            return $this->redirectToRoute(
                'scheduling_job_show',
                [
                    'id' => $job->getId(),
                ]
            );
        }

        return $this->render(
            '@scheduling/job/edit.html.twig',
            [
                'form' => $form->createView(),
                'isEdit' => $isEdit,
                'job' => $job,
                'gpsLat' => $job->getGpsLat() ?? 0,
                'gpsLon' => $job->getGpsLon() ?? 0,
                'zoom' => $isEdit ? Map::DEFAULT_ZOOM : 1,
            ]
        );
    }
}
