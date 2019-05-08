<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Api\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use SchedulingBundle\Api\Map\JobTaskMap;
use SchedulingBundle\Api\Mapper\JobTaskMapper;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobTask;
use SchedulingBundle\Service\Facade\JobTaskFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 */
class JobTaskController extends BaseController implements AppKeyAuthenticatedInterface
{
    use JobPrivilegesTrait;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var JobTaskFacade
     */
    private $facade;

    /**
     * @var JobTaskMapper
     */
    private $mapper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        Validator $validator,
        JobTaskFacade $facade,
        JobTaskMapper $mapper,
        EntityManagerInterface $em
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
    }

    /**
     * @Get("/scheduling/jobs/tasks/{id}", name="job_task_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(JobTask $task): View
    {
        $this->checkPrivileges($task->getJob(), Permission::VIEW);

        return $this->view(
            $this->mapper->reflect($task)
        );
    }

    /**
     * @Get("/scheduling/jobs/tasks", name="job_task_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     * @QueryParam(
     *     name="jobId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=false,
     *     description="job ID"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $jobId = $paramFetcher->get('jobId');
        $job = $this->em->find(Job::class, $jobId);
        if (! $job) {
            throw new NotFoundHttpException('Job object not found.');
        }

        $this->checkPrivileges($job, Permission::EDIT);

        return $this->view(
            $this->mapper->reflectCollection($job->getTasks())
        );
    }

    /**
     * @Post("/scheduling/jobs/tasks", name="job_task_add", options={"method_prefix"=false})
     * @ParamConverter("map", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function postAction(JobTaskMap $map, string $version): View
    {
        $task = new JobTask();
        $this->mapper->map($map, $task);
        $this->validator->validate($task, $this->mapper->getFieldsDifference());

        $this->checkPrivileges($task->getJob(), Permission::EDIT);

        $this->facade->handleNew($task);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($task),
            'api_job_task_get',
            [
                'version' => $version,
                'id' => $task->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/scheduling/jobs/tasks/{id}",
     *     name="job_task_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("map", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function patchAction(JobTask $task, JobTaskMap $map): View
    {
        $this->checkPrivileges($task->getJob(), Permission::EDIT);

        $this->mapper->map($map, $task);
        $this->validator->validate($task, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($task);

        return $this->view(
            $this->mapper->reflect($task)
        );
    }

    /**
     * @Delete(
     *     "/scheduling/jobs/tasks/{id}",
     *     name="job_task_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function deleteAction(JobTask $task): View
    {
        $this->checkPrivileges($task->getJob(), Permission::EDIT);

        $this->facade->handleDelete($task);

        return $this->view(null, 200);
    }
}
