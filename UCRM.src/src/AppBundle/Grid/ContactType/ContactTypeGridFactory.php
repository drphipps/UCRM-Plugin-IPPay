<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ContactType;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\ContactTypeController;
use AppBundle\DataProvider\ContactTypeDataProvider;
use AppBundle\Entity\ContactType;

class ContactTypeGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var ContactTypeDataProvider
     */
    private $contactTypeDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        ContactTypeDataProvider $contactTypeDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->contactTypeDataProvider = $contactTypeDataProvider;
    }

    public function create(): Grid
    {
        $qb = $this->contactTypeDataProvider->getGridModel();
        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->addIdentifier('c_id', 'c.id');
        $grid->setDefaultSort('c_id');
        $grid->setRowUrl('contact_type_show');

        $grid->attached();

        $grid->addTextColumn('c_name', 'c.name', 'Name')->setSortable();
        $grid->addEditActionButton('contact_type_edit', [], ContactTypeController::class);
        $grid
            ->addDeleteActionButton('contact_type_delete', [], ContactTypeController::class)
            ->addRenderCondition(
                function ($row) {
                    return $row['c_id'] >= ContactType::CONTACT_TYPE_MAX_SYSTEM_ID;
                }
            );

        return $grid;
    }
}
