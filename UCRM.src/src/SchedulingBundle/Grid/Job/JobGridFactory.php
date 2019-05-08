<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Grid\Job;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Component\MultiActionGroup;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\Entity\User;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Util\DurationFormatter;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;
use SchedulingBundle\Service\Facade\JobFacade;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class JobGridFactory
{
    /**
     * @var DurationFormatter
     */
    private $durationFormatter;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var GridHelper
     */
    private $gridHelper;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    /**
     * @var JobFacade
     */
    private $jobFacade;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    public function __construct(
        DurationFormatter $durationFormatter,
        EntityManagerInterface $em,
        Formatter $formatter,
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        JobDataProvider $jobDataProvider,
        JobFacade $jobFacade,
        PermissionGrantedChecker $permissionGrantedChecker,
        TokenStorageInterface $tokenStorage,
        ClientDataProvider $clientDataProvider
    ) {
        $this->durationFormatter = $durationFormatter;
        $this->em = $em;
        $this->formatter = $formatter;
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->jobDataProvider = $jobDataProvider;
        $this->jobFacade = $jobFacade;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->tokenStorage = $tokenStorage;
        $this->clientDataProvider = $clientDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->jobDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('j_id', 'j.id');
        $grid->setRowUrl('scheduling_job_show');
        $grid->setDefaultSort('j_date', Grid::ASC);

        $grid->attached();

        $grid->addTextColumn('j_title', 'j.title', 'Title')->setSortable();
        $grid->addTextColumn('j_status', 'j.status', 'Status')
            ->setReplacements(Job::STATUSES)
            ->setSortable();
        $grid
            ->addCustomColumn(
                'j_date',
                'Date',
                function ($row) {
                    return $row['j_date']
                        ? $this->formatter->formatDate($row['j_date'], Formatter::DEFAULT, Formatter::SHORT)
                        : BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();
        $grid
            ->addCustomColumn(
                'j_duration',
                'Duration',
                function ($row) {
                    return $row['j_duration']
                        ? $this->durationFormatter->format($row['j_duration'] * 60, DurationFormatter::SHORT)
                        : BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'j_assigned_user',
                'Assigned user',
                function ($row) {
                    return $row['j_assigned_user'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'j_client',
                'Client',
                function ($row) {
                    return $row['j_client'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid->addDateFilter('date', 'j.date', 'Date', true);
        $grid->addSelectFilter('status', 'j.status', 'Status', Job::STATUSES);

        $grid->addSelectFilter(
            'assigned_user',
            'j.assignedUser',
            'Assigned user',
            $this->em->getRepository(User::class)->findAllAdminsForm(),
            true
        );

        $grid->addSelectFilter(
            'client',
            'j.client',
            'Client',
            $this->clientDataProvider->getAllClientsForm(),
            true
        );

        $exportCsv = $grid->addMultiAction(
            'export-csv',
            'Export CSV',
            function () use ($grid) {
                return $this->exportCsvAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered jobs into CSV file.',
            null,
            true,
            false
        );

        $exportPdf = $grid->addMultiAction(
            'export-pdf',
            'Export PDF',
            function () use ($grid) {
                return $this->exportPdfAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered jobs into PDF file.',
            null,
            true,
            false
        );

        $group = new MultiActionGroup(
            'export',
            'Export',
            [
                'button--primary',
            ],
            [
                $exportPdf,
                $exportCsv,
            ],
            'ucrm-icon--export'
        );
        $grid->addMultiActionGroup($group);

        return $grid;
    }

    private function exportPdfAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        $ids = $this->removeNotAllowedJobs($ids);

        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no jobs to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $name = $this->gridHelper->transChoice(
            '%filetype% overview of %count% jobs',
            $count,
            [
                '%count%' => $count,
                '%filetype%' => 'PDF',
            ]
        );

        $this->jobFacade->preparePdfDownload($name, $ids, $this->getUser());

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function exportCsvAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        $ids = $this->removeNotAllowedJobs($ids);

        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no jobs to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $name = $this->gridHelper->transChoice(
            '%filetype% overview of %count% jobs',
            $count,
            [
                '%count%' => $count,
                '%filetype%' => 'CSV',
            ]
        );

        $this->jobFacade->prepareCsvDownload($name, $ids, $this->getUser());

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function removeNotAllowedJobs(array $jobIds): array
    {
        if ($this->permissionGrantedChecker->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)) {
            return $jobIds;
        }

        return $this->jobDataProvider->getJobsByUser($this->getUser(), $jobIds);
    }

    private function getUser(): User
    {
        $user = $this->tokenStorage->getToken()->getUser();
        assert($user instanceof User);

        return $user;
    }
}
