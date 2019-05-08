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
use AppBundle\Service\DownloadResponseFactory;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use SchedulingBundle\Api\Map\JobAttachmentMap;
use SchedulingBundle\Api\Mapper\JobAttachmentMapper;
use SchedulingBundle\DataProvider\JobAttachmentDataProvider;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\JobAttachment;
use SchedulingBundle\FileManager\JobAttachmentFileManager;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobAttachmentFacade;
use SchedulingBundle\Service\Factory\JobAttachmentFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 */
class JobAttachmentController extends BaseController implements AppKeyAuthenticatedInterface
{
    use JobPrivilegesTrait;

    /**
     * @var JobAttachmentDataProvider
     */
    private $dataProvider;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var JobAttachmentFacade
     */
    private $facade;

    /**
     * @var JobAttachmentFactory
     */
    private $jobAttachmentFactory;

    /**
     * @var JobAttachmentFileManager
     */
    private $jobAttachmentFileManager;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    /**
     * @var JobAttachmentMapper
     */
    private $mapper;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var Validator
     */
    private $validator;

    public function __construct(
        JobAttachmentDataProvider $dataProvider,
        DownloadResponseFactory $downloadResponseFactory,
        JobAttachmentFacade $facade,
        JobAttachmentFactory $jobAttachmentFactory,
        JobAttachmentFileManager $jobAttachmentFileManager,
        JobDataProvider $jobDataProvider,
        JobAttachmentMapper $mapper,
        PermissionGrantedChecker $permissionGrantedChecker,
        Validator $validator
    ) {
        $this->dataProvider = $dataProvider;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->facade = $facade;
        $this->jobAttachmentFactory = $jobAttachmentFactory;
        $this->jobAttachmentFileManager = $jobAttachmentFileManager;
        $this->jobDataProvider = $jobDataProvider;
        $this->mapper = $mapper;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->validator = $validator;
    }

    /**
     * @Get(
     *     "/scheduling/jobs/attachments/{id}",
     *     name="job_attachments_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(JobAttachment $jobAttachment): View
    {
        $this->checkPrivileges($jobAttachment->getJob(), Permission::VIEW);

        return $this->view(
            $this->mapper->reflect($jobAttachment)
        );
    }

    /**
     * @Get(
     *     "/scheduling/jobs/attachments/{id}/file",
     *     name="job_attachments_get_file",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function getFileAction(JobAttachment $jobAttachment): BinaryFileResponse
    {
        $this->checkPrivileges($jobAttachment->getJob(), Permission::VIEW);

        return $this->downloadResponseFactory->createFromFile(
            $this->jobAttachmentFileManager->getFilePath($jobAttachment)
        );
    }

    /**
     * @Get(
     *     "/scheduling/jobs/attachments",
     *     name="job_atachments_collection_get",
     *     options={"method_prefix"=false}
     * )
     * @ViewHandler()
     * @Permission("public")
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
        if (($jobId = $paramFetcher->get('jobId')) && (! $job = $this->jobDataProvider->getById((int) $jobId))) {
            throw new NotFoundHttpException('Job object not found.');
        }

        if ($job ?? false) {
            $this->checkPrivileges($job, Permission::VIEW);
        }

        if (
            $this->getUser() instanceof User
            && ! $this->permissionGrantedChecker->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
        ) {
            $this->permissionGrantedChecker->denyAccessUnlessGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
            $assignedUser = $this->getUser();
        }

        return $this->view(
            $this->mapper->reflectCollection(
                $this->dataProvider->getAllJobAttachments($job ?? null, $assignedUser ?? null)
            )
        );
    }

    /**
     * @Post(
     *     "/scheduling/jobs/attachments",
     *     name="job_attachment_add",
     *     options={"method_prefix"=false}
     * )
     * @ParamConverter("jobAttachmentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function postAction(JobAttachmentMap $jobAttachmentMap, string $version): View
    {
        $jobAttachmentFile = $this->jobAttachmentFileManager->createTempFileFromAPI($jobAttachmentMap);
        $jobAttachment = $this->jobAttachmentFactory->createFromFile(
            $jobAttachmentFile,
            $jobAttachmentMap->filename
        );

        $this->mapper->map($jobAttachmentMap, $jobAttachment);
        $this->validator->validate($jobAttachment, $this->mapper->getFieldsDifference());

        $this->checkPrivileges($jobAttachment->getJob(), Permission::EDIT);

        $this->facade->handleNew($jobAttachment, $jobAttachmentFile);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($jobAttachment),
            'api_job_attachments_get',
            [
                'version' => $version,
                'id' => $jobAttachment->getId(),
            ]
        );
    }

    /**
     * @Patch(
     *     "/scheduling/jobs/attachments/{id}",
     *     name="job_attachment_edit",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ParamConverter("jobAttachmentMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("public")
     */
    public function patchAction(JobAttachment $jobAttachment, JobAttachmentMap $jobAttachmentMap): View
    {
        $this->checkPrivileges($jobAttachment->getJob(), Permission::EDIT);

        $this->mapper->map($jobAttachmentMap, $jobAttachment);
        $this->validator->validate($jobAttachment, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($jobAttachment);

        return $this->view(
            $this->mapper->reflect($jobAttachment)
        );
    }

    /**
     * @Delete(
     *     "/scheduling/jobs/attachments/{id}",
     *     name="job_attachment_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function deleteAction(JobAttachment $jobAttachment): View
    {
        $this->checkPrivileges($jobAttachment->getJob(), Permission::EDIT);

        $this->facade->handleDelete($jobAttachment);

        return $this->view(null, 200);
    }
}
