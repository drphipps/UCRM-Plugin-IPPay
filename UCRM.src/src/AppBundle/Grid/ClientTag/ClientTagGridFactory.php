<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ClientTag;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\ClientTagController;
use AppBundle\DataProvider\ClientTagDataProvider;

class ClientTagGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var ClientTagDataProvider
     */
    private $clientTagDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        ClientTagDataProvider $clientTagDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->clientTagDataProvider = $clientTagDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->clientTagDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('t_id', 't.id');
        $grid->setDefaultSort('t_id');
        $grid->setRowUrl('client_tag_show');

        $grid->attached();

        $grid->addTextColumn('t_name', 't.name', 'Name')->setSortable();
        $grid->addEditActionButton('client_tag_edit', [], ClientTagController::class);
        $grid->addDeleteActionButton('client_tag_delete', [], ClientTagController::class);

        return $grid;
    }
}
