<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Grid\MyJobs;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\Entity\User;
use AppBundle\Util\DurationFormatter;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use SchedulingBundle\Controller\SchedulingControllerInterface;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;

class MyJobsGridFactory
{
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
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var DurationFormatter
     */
    private $durationFormatter;

    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        JobDataProvider $jobDataProvider,
        EntityManagerInterface $em,
        Formatter $formatter,
        DurationFormatter $durationFormatter,
        ClientDataProvider $clientDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->jobDataProvider = $jobDataProvider;
        $this->em = $em;
        $this->formatter = $formatter;
        $this->durationFormatter = $durationFormatter;
        $this->clientDataProvider = $clientDataProvider;
    }

    public function create(User $user): Grid
    {
        $qb = $this->jobDataProvider->getGridModel($user);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('j_id', 'j.id');
        $grid->addRouterUrlParam('filterType', SchedulingControllerInterface::FILTER_MY);
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
            'client',
            'j.client',
            'Client',
            $this->clientDataProvider->getAllClientsForm(),
            true
        );

        return $grid;
    }
}
