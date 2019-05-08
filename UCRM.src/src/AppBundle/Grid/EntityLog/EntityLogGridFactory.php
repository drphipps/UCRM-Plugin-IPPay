<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\EntityLog;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Site;
use AppBundle\Facade\EntityLogFacade;
use AppBundle\Service\EntityLog\EntityLogRenderer;
use AppBundle\Util\Formatter;

class EntityLogGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var EntityLogFacade
     */
    private $entityLogFacade;

    /**
     * @var EntityLogRenderer
     */
    private $entityLogRenderer;

    public function __construct(
        GridFactory $gridFactory,
        EntityLogFacade $entityLogFacade,
        EntityLogRenderer $entityLogRenderer
    ) {
        $this->gridFactory = $gridFactory;
        $this->entityLogFacade = $entityLogFacade;
        $this->entityLogRenderer = $entityLogRenderer;
    }

    public function create(?Site $site): Grid
    {
        $qb = $this->entityLogFacade->getGridModel($site);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);

        $grid->setDefaultSort(null);
        $grid->addIdentifier('el_id', 'el.id');
        $grid->setRowUrl('system_log_detail');
        $grid->setRowUrlIsModal();

        if ($site) {
            $grid->addRouterUrlParam('id', $site->getId());
            $grid->setRouterUrlSuffix('#tab-system-log');
        }

        $grid->attached();

        $grid->addCustomColumn(
            'el_log',
            'Message',
            function ($row) {
                /** @var EntityLog $entityLog */
                $entityLog = $row[0];

                return $this->entityLogRenderer->renderMessage($entityLog);
            }
        );

        $grid
            ->addTwigFilterColumn(
                'el_created_date',
                'el.createdDate',
                'Date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::MEDIUM]
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'u_fullname',
                'User',
                function ($row) {
                    return $row['u_fullname'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();
        $grid->addTextColumn('el_user_type', 'el.userType', 'Type')
            ->setReplacements(EntityLog::USER_TYPE)
            ->setSortable();

        return $grid;
    }
}
