<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Grid\JobLogsView;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Entity\EntityLog;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\EntityLog\EntityLogRenderer;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Html;
use SchedulingBundle\DataProvider\JobLogsViewDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Entity\JobComment;
use SchedulingBundle\Entity\JobLogsView;

class JobLogsViewGridFactory
{
    /**
     * @var JobLogsViewDataProvider
     */
    private $dataProvider;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var EntityLogRenderer
     */
    private $entityLogRenderer;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(
        JobLogsViewDataProvider $dataProvider,
        EntityManager $em,
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        EntityLogRenderer $entityLogRenderer,
        PermissionGrantedChecker $permissionGrantedChecker
    ) {
        $this->dataProvider = $dataProvider;
        $this->em = $em;
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->entityLogRenderer = $entityLogRenderer;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    public function create(Job $job, array $filters = []): Grid
    {
        $qb = $this->dataProvider->getGridModel($job, $filters);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setDefaultSort(null);
        $grid->addIdentifier('jlw_id', 'jlw.logId');
        $grid->addRouterUrlParam('id', $job->getId());
        $grid->setRoute('scheduling_job_show');

        $grid->setRowUrl(
            function ($row) {
                /** @var JobLogsView $entity */
                $entity = $row[0];
                if ($entity->getLogType() === JobLogsView::LOG_TYPE_ENTITY_LOG) {
                    return 'system_log_detail';
                }

                return null;
            }
        );
        $grid->setRowUrlIsModal();

        foreach ($filters as $key => $value) {
            $grid->addRouterUrlParam($key, $value, true);
        }

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'el_type',
                'Type',
                function ($row) {
                    /** @var JobLogsView $entity */
                    $entity = $row[0];
                    switch ($entity->getLogType()) {
                        case JobLogsView::LOG_TYPE_ENTITY_LOG:
                            $type = $this->gridHelper->trans('System log');
                            $typeClass = 'grid-row-type--success';

                            break;
                        case JobLogsView::LOG_TYPE_JOB_COMMENT:
                            $type = $this->gridHelper->trans('Comment');
                            $typeClass = 'grid-row-type--primary';

                            break;
                        default:
                            throw new \RuntimeException('Unknown JobLogsView logType.');
                    }

                    $typeEl = Html::el(
                        'span',
                        [
                            'class' => [
                                'grid-row-type',
                                $typeClass,
                            ],
                        ]
                    );
                    $typeEl->setText($type);

                    return (string) $typeEl;
                }
            )
            ->setWidth(10);

        $grid
            ->addRawCustomColumn(
                'jlw_message',
                'Message',
                function ($row) {
                    /** @var JobLogsView $jobLog */
                    $jobLog = $row[0];
                    if ($jobLog->getLogType() === JobLogsView::LOG_TYPE_ENTITY_LOG) {
                        $entityLog = $this->em->getRepository(EntityLog::class)->find($jobLog->getLogId());

                        return $this->entityLogRenderer->renderMessage($entityLog);
                    }

                    return nl2br(htmlspecialchars($jobLog->getMessage() ?? '', ENT_QUOTES)) ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setWidth(40);

        $grid->addCustomColumn(
            'el_user',
            'User',
            function ($row) {
                /** @var JobLogsView $entity */
                $entity = $row[0];

                switch ($entity->getLogType()) {
                    case JobLogsView::LOG_TYPE_ENTITY_LOG:
                        $entityLog = $this->em->getRepository(EntityLog::class)->find($entity->getLogId());
                        $message = $entityLog->getUser()
                            ? $entityLog->getUser()->getNameForView()
                            : $this->gridHelper->trans('System');

                        break;
                    case JobLogsView::LOG_TYPE_JOB_COMMENT:
                        $comment = $this->em->getRepository(JobComment::class)->find($entity->getLogId());
                        $message = $comment->getUser()
                            ? $comment->getUser()->getNameForView()
                            : BaseColumn::EMPTY_COLUMN;

                        break;
                    default:
                        throw new \RuntimeException('Unknown JobLogsView logType.');
                }

                return $message;
            }
        );

        $grid
            ->addTwigFilterColumn(
                'jlw_created_date',
                'jlw.createdDate',
                'Date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        if ($this->permissionGrantedChecker->isGrantedSpecial(SpecialPermission::JOB_COMMENT_EDIT)) {
            $editButton = $grid->addEditActionButton('scheduling_job_comment_edit', $filters, null, true);
            $editButton->addRenderCondition(
                function ($row) {
                    /** @var JobLogsView $jobLog */
                    $jobLog = $row[0];

                    return $jobLog->getLogType() === JobLogsView::LOG_TYPE_JOB_COMMENT;
                }
            );

            $deleteButon = $grid->addDeleteActionButton('scheduling_job_comment_delete', [], null, ['ajax']);
            $deleteButon->addRenderCondition(
                function ($row) {
                    /** @var JobLogsView $jobLog */
                    $jobLog = $row[0];

                    return $jobLog->getLogType() === JobLogsView::LOG_TYPE_JOB_COMMENT;
                }
            );
        }

        return $grid;
    }
}
