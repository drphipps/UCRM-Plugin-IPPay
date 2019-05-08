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
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Util\DateTimeFactory;
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
use SchedulingBundle\Api\Map\JobCommentMap;
use SchedulingBundle\Api\Mapper\JobCommentMapper;
use SchedulingBundle\DataProvider\JobCommentDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobCommentFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 */
class JobCommentController extends BaseController implements AppKeyAuthenticatedInterface
{
    use JobPrivilegesTrait;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var JobCommentFacade
     */
    private $facade;

    /**
     * @var JobCommentMapper
     */
    private $mapper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var JobCommentDataProvider
     */
    private $dataProvider;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        Validator $validator,
        JobCommentFacade $facade,
        JobCommentMapper $mapper,
        EntityManagerInterface $em,
        JobCommentDataProvider $dataProvider,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->dataProvider = $dataProvider;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    /**
     * @Get(
     *     "/scheduling/jobs/comments/{id}",
     *     name="job_comment_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(JobComment $jobComment): View
    {
        $this->checkPrivileges($jobComment->getJob(), Permission::VIEW);

        return $this->view(
            $this->mapper->reflect($jobComment)
        );
    }

    /**
     * @Get("/scheduling/jobs/comments", name="job_comments_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="userId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="user ID"
     * )
     * @QueryParam(
     *     name="jobId",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="job ID"
     * )
     *
     * @throws \Exception
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $user = null;
        if ($userId = $paramFetcher->get('userId')) {
            $user = $this->em->getRepository(User::class)->findOneBy(
                [
                    'id' => $userId,
                    'role' => User::ADMIN_ROLES,
                ]
            );
            if (! $user) {
                throw new NotFoundHttpException('User object not found.');
            }
        }

        $job = null;
        if ($jobId = $paramFetcher->get('jobId')) {
            $job = $this->em->find(Job::class, $jobId);
            if (! $job) {
                throw new NotFoundHttpException('Job object not found.');
            }
            $this->checkPrivileges($job, Permission::VIEW);
        }

        if (
            $this->getUser() instanceof User
            && ! $this->permissionGrantedChecker->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
        ) {
            $this->permissionGrantedChecker->denyAccessUnlessGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
            $assignedUser = $this->getUser();
        }

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $endDate = DateTimeFactory::createDate($endDate);
                $endDate->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $jobComments = $this->dataProvider->getAllJobComments($job, $user, $startDate, $endDate, $assignedUser ?? null);

        return $this->view(
            $this->mapper->reflectCollection($jobComments)
        );
    }

    /**
     * @Post("/scheduling/jobs/comments", name="job_comment_add", options={"method_prefix"=false})
     * @ParamConverter("jobCommentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function postAction(JobCommentMap $jobCommentMap, string $version): View
    {
        $jobComment = new JobComment();
        $this->mapper->map($jobCommentMap, $jobComment);
        $this->validator->validate($jobComment, $this->mapper->getFieldsDifference());

        $this->checkPrivileges($jobComment->getJob(), Permission::EDIT);

        $this->facade->handleNew($jobComment);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($jobComment),
            'api_job_comment_get',
            [
                'version' => $version,
                'id' => $jobComment->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/scheduling/jobs/comments/{id}",
     *     name="job_comment_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("jobCommentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function patchAction(JobComment $jobComment, JobCommentMap $jobCommentMap): View
    {
        $this->checkPrivileges($jobComment->getJob(), Permission::EDIT);

        if (! $this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::JOB_COMMENT_EDIT)) {
            throw new HttpException(403, 'Access denied.');
        }

        $this->mapper->map($jobCommentMap, $jobComment);
        $this->validator->validate($jobComment, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($jobComment);

        return $this->view(
            $this->mapper->reflect($jobComment)
        );
    }

    /**
     * @Delete(
     *     "/scheduling/jobs/comments/{id}",
     *     name="job_comment_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function deleteAction(JobComment $jobComment): View
    {
        $this->checkPrivileges($jobComment->getJob(), Permission::EDIT);

        if (! $this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::JOB_COMMENT_EDIT)) {
            throw new HttpException(403, 'Access denied.');
        }

        $this->facade->handleDelete($jobComment);

        return $this->view(null, 200);
    }
}
