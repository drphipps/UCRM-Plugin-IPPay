<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\AppKey;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\AppKeyController;
use AppBundle\DataProvider\AppKeyDataProvider;
use AppBundle\Entity\AppKey;

class AppKeyGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var AppKeyDataProvider
     */
    private $appKeyDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        AppKeyDataProvider $appKeyDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->appKeyDataProvider = $appKeyDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->appKeyDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('ak_id', 'ak.id');
        $grid->setRowUrl('app_key_show');
        $grid->setDefaultSort('ak_name');

        $grid->attached();

        $grid->addTextColumn('ak_name', 'ak.name', 'Name')
            ->setSortable();
        $grid->addTextColumn('ak_type', 'ak.type', 'Type')
            ->setReplacements(AppKey::TYPE_REPLACE_READABLE)
            ->setSortable();

        $grid->addEditActionButton('app_key_edit', [], AppKeyController::class);
        $grid->addDeleteActionButton('app_key_delete', [], AppKeyController::class);

        return $grid;
    }
}
