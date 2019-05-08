<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Security\Permission;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobTask;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobTaskFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/scheduling/job/task")
 */
class JobTaskController extends BaseController
{
    /**
     * @Route("/new/{job}", name="scheduling_job_task_new", requirements={"job": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function newAction(Request $request, Job $job): Response
    {
        $this->checkEditPrivileges($job);

        $label = $request->get('label', '');
        if ($label === '') {
            $this->addTranslatedFlash('error', 'Task label can\'t be empty.');

            return $this->createAjaxResponse();
        }

        $task = new JobTask();
        $task->setJob($job);
        $task->setLabel($label);
        $response = [];

        $violations = $this->get(ValidatorInterface::class)->validate($task);
        if ($violations->count()) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $this->addTranslatedFlash(
                    'error',
                    $violation->getMessageTemplate(),
                    $violation->getPlural(),
                    $violation->getParameters(),
                    'validators'
                );
            }
        } else {
            $this->get(JobTaskFacade::class)->handleNew($task);
            $response['task'] = $task->toArray();

            $this->addTranslatedFlash('success', 'Item has been saved.');
        }

        return $this->createAjaxResponse($response);
    }

    /**
     * @Route("/{task}/edit", name="scheduling_job_task_edit", requirements={"task": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function editAction(Request $request, JobTask $task): Response
    {
        $this->checkEditPrivileges($task->getJob());

        $label = $request->get('label', '');
        if ($label === '') {
            $this->addTranslatedFlash('error', 'Task label can\'t be empty.');

            return $this->createAjaxResponse();
        }

        $response = [];

        $task->setLabel($label);
        $violations = $this->get(ValidatorInterface::class)->validate($task);
        if ($violations->count()) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $this->addTranslatedFlash(
                    'error',
                    $violation->getMessageTemplate(),
                    $violation->getPlural(),
                    $violation->getParameters(),
                    'validators'
                );
            }
        } else {
            $this->get(JobTaskFacade::class)->handleEdit($task);
            $response['task'] = $task->toArray();

            $this->addTranslatedFlash('success', 'Item has been saved.');
        }

        return $this->createAjaxResponse($response);
    }

    /**
     * @Route("/{task}/delete", name="scheduling_job_task_delete", requirements={"task": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function deleteAction(JobTask $task): Response
    {
        $this->checkEditPrivileges($task->getJob());

        $this->get(JobTaskFacade::class)->handleDelete($task);

        $this->addTranslatedFlash('success', 'Item has been removed.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{task}/change-closed", name="scheduling_job_task_close", requirements={"task": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function changeClosedAction(Request $request, JobTask $task): Response
    {
        $this->checkEditPrivileges($task->getJob());

        $task->setClosed((bool) (int) $request->get('closed'));
        $this->get(JobTaskFacade::class)->handleEdit($task);

        $this->addTranslatedFlash('success', 'Item has been saved.');

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/{task}/move", name="scheduling_job_task_move", requirements={"task": "\d+"}, options={"expose": true})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("guest")
     */
    public function moveAction(Request $request, JobTask $task): Response
    {
        $this->checkEditPrivileges($task->getJob());

        $task->setSequence($request->query->getInt('sequence'));
        $this->get(JobTaskFacade::class)->handleEdit($task);

        $this->addTranslatedFlash('success', 'Item has been saved.');

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
