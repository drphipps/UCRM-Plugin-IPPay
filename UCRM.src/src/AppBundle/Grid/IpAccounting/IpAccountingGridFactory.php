<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\IpAccounting;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Facade\IpAccountingFacade;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Formatter;

class IpAccountingGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var IpAccountingFacade
     */
    private $ipAccountingFacade;

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(GridFactory $gridFactory, IpAccountingFacade $ipAccountingFacade, Formatter $formatter)
    {
        $this->gridFactory = $gridFactory;
        $this->ipAccountingFacade = $ipAccountingFacade;
        $this->formatter = $formatter;
    }

    public function create(): Grid
    {
        $qb = $this->ipAccountingFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('a_id', 'a.ip');
        $grid->setRouterUrlSuffix('#tab-ip-accounting');
        $grid->setDefaultSort('last', Grid::DESC);

        $grid->attached();

        $grid->addTwigFilterColumn('ip', 'a.ip', 'IP Address', 'long2ip')
            ->setSortable();

        $grid->addTwigFilterColumn('upload', 'SUM(a.upload)', 'Upload', 'bytesToSize')
            ->setSortable();

        $grid->addTwigFilterColumn('download', 'SUM(a.download)', 'Download', 'bytesToSize')
            ->setSortable();

        $grid
            ->addCustomColumn(
                'last',
                'Last activity',
                function ($row) {
                    return $this->formatter->formatDate(
                        DateTimeFactory::createDate($row['last']),
                        Formatter::DEFAULT,
                        Formatter::NONE
                    );
                }
            )
            ->setSortable();

        return $grid;
    }
}
