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
use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
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
use SchedulingBundle\Api\Map\JobMap;
use SchedulingBundle\Api\Mapper\JobMapper;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Request\JobCollectionRequest;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\Ticket;

/**
 * @NamePrefix("api_")
 */
class JobController extends BaseController implements AppKeyAuthenticatedInterface
{
    use JobPrivilegesTrait;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var JobFacade
     */
    private $facade;

    /**
     * @var JobMapper
     */
    private $mapper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var JobDataProvider
     */
    private $dataProvider;

    public function __construct(
        Validator $validator,
        JobFacade $facade,
        JobMapper $mapper,
        EntityManagerInterface $em,
        JobDataProvider $dataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @Get("/scheduling/jobs/{id}", name="job_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(Job $job): View
    {
        $this->checkPrivileges($job, Permission::VIEW);

        return $this->view(
            $this->mapper->reflect($job)
        );
    }

    /**
     * @Get("/scheduling/jobs", name="job_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     * @QueryParam(
     *     name="dateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="dateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="clientId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="client ID"
     * )
     * @QueryParam(
     *     name="assignedUserId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="assigned user ID"
     * )
     * @QueryParam(
     *     name="ticketId",
     *     requirements="\d+|null",
     *     strict=true,
     *     nullable=true,
     *     description="assigned ticket ID"
     * )
     * @QueryParam(
     *     name="statuses",
     *     requirements=@Assert\All(@Assert\Choice(Job::STATUSES_NUMERIC)),
     *     strict=true,
     *     nullable=true,
     *     description="select only jobs in one of the given statuses"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);

        $filterNullRelations = [];
        $client = null;
        if ($clientId = $paramFetcher->get('clientId')) {
            if ($clientId === 'null') {
                $filterNullRelations[] = 'assignedUser';
            } else {
                $client = $this->em->find(Client::class, $clientId);
                if (! $client) {
                    throw new NotFoundHttpException('Client object not found.');
                }

                if ($client->isDeleted()) {
                    throw new NotFoundHttpException(
                        'Client is archived. All actions are prohibited. You can only restore the client.'
                    );
                }
            }
        }

        $user = null;
        if ($this->getUser() instanceof User && ! $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)) {
            $user = $this->getUser();
        } elseif ($assignedUserId = $paramFetcher->get('assignedUserId')) {
            if ($assignedUserId === 'null') {
                $filterNullRelations[] = 'assignedUser';
            } else {
                $user = $this->em->getRepository(User::class)->findOneBy(
                    [
                        'id' => $assignedUserId,
                        'role' => User::ADMIN_ROLES,
                    ]
                );
                if (! $user) {
                    throw new NotFoundHttpException('User object not found.');
                }
            }
        }

        $ticket = null;
        if ($ticketId = $paramFetcher->get('ticketId')) {
            if ($ticketId === 'null') {
                $filterNullRelations[] = 'assignedTicket';
            } else {
                $ticket = $this->em->find(Ticket::class, $ticketId);
                if (! $ticket) {
                    throw new NotFoundHttpException('Ticket object not found.');
                }
            }
        }

        if ($startDate = $paramFetcher->get('dateFrom')) {
            try {
                $startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('dateTo')) {
            try {
                $endDate = DateTimeFactory::createDate($endDate);
                $endDate->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($statuses = $paramFetcher->get('statuses')) {
            $statuses = Helpers::typeCastAll('int', $statuses);
        }

        $jobCollectionRequest = new JobCollectionRequest();
        $jobCollectionRequest->user = $user;
        $jobCollectionRequest->client = $client;
        $jobCollectionRequest->ticket = $ticket;
        $jobCollectionRequest->startDate = $startDate;
        $jobCollectionRequest->endDate = $endDate;
        $jobCollectionRequest->statuses = $statuses;
        $jobCollectionRequest->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));
        $jobCollectionRequest->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $jobCollectionRequest->filterNullRelations = $filterNullRelations;

        $jobs = $this->dataProvider->getAllJobs($jobCollectionRequest);

        return $this->view(
            $this->mapper->reflectCollection($jobs)
        );
    }

    /**
     * @Post("/scheduling/jobs", name="job_add", options={"method_prefix"=false})
     * @ParamConverter("jobMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function postAction(JobMap $jobMap, string $version): View
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, SchedulingPermissions::JOBS_MY);

        $job = new Job();
        $this->mapper->map($jobMap, $job);
        $this->validator->validate($job, $this->mapper->getFieldsDifference());
        $this->facade->handleNew($job);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($job),
            'api_job_get',
            [
                'version' => $version,
                'id' => $job->getId(),
            ]
        );
    }

    /**
     * @Patch("/scheduling/jobs/{id}", name="job_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("jobMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function patchAction(Job $job, JobMap $jobMap): View
    {
        $this->checkPrivileges($job, Permission::EDIT);

        $jobBeforeEdit = clone $job;
        $this->mapper->map($jobMap, $job);
        $this->validator->validate($job, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($job, $jobBeforeEdit);

        return $this->view(
            $this->mapper->reflect($job)
        );
    }

    /**
     * @Delete(
     *     "/scheduling/jobs/{id}",
     *     name="job_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function deleteAction(Job $job): View
    {
        $this->checkPrivileges($job, Permission::EDIT);

        $this->facade->handleDelete($job);

        return $this->view(null, 200);
    }
}
