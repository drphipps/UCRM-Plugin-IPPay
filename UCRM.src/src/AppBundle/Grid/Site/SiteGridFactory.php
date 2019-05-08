<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Site;

use AppBundle\Component\Elastic;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\SiteController;
use AppBundle\Facade\SiteFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SiteGridFactory
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
     * @var Formatter
     */
    private $formatter;

    /**
     * @var SiteFacade
     */
    private $siteFacade;

    /**
     * @var Elastic\Search
     */
    private $elasticSearch;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        SiteFacade $siteFacade,
        Elastic\Search $elasticSearch
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->siteFacade = $siteFacade;
        $this->elasticSearch = $elasticSearch;
    }

    public function create(): Grid
    {
        $qb = $this->siteFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 's_id');
        $grid->setRowUrl('site_show');
        $grid->addIdentifier('s_id', 's.id');

        if (empty($grid->getActiveFilter('search'))) {
            $grid->setDefaultSort('s_name');
        }

        $grid->attached();

        $grid->addTextColumn('s_name', 's.name', 'Name')->setSortable();
        $grid->addTextColumn('s_address', 's.address', 'Address');

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by name or address'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter(
            'search',
            's.id',
            $tooltip,
            Elastic\Search::TYPE_SITE,
            $this->gridHelper->trans('Search')
        );

        $grid->addEditActionButton('site_edit', [], SiteController::class);

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to delete these sites?',
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, SiteController::class);

        list($deleted, $failed) = $this->siteFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% sites.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% sites could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
