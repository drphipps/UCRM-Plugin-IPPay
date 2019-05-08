<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\UserGroup;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\UserGroupController;
use AppBundle\Entity\UserGroup;
use AppBundle\Facade\UserGroupFacade;

class UserGroupGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var UserGroupFacade
     */
    private $userGroupFacade;

    public function __construct(
        GridFactory $gridFactory,
        UserGroupFacade $userGroupFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->userGroupFacade = $userGroupFacade;
    }

    public function create(): Grid
    {
        $qb = $this->userGroupFacade->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('ug_id', 'ug.id');
        $grid->setDefaultSort('ug_name');
        $grid->setRowUrl('user_group_show');

        $grid->attached();

        $grid->addTextColumn('ug_name', 'ug.name', 'Name')->setSortable();
        $grid->addEditActionButton('user_group_edit', [], UserGroupController::class);
        $grid
            ->addDeleteActionButton('user_group_delete', [], UserGroupController::class)
            ->addRenderCondition(
                function ($row) {
                    return $row['ug_id'] >= UserGroup::USER_GROUP_MAX_SYSTEM_ID;
                }
            );

        return $grid;
    }
}
