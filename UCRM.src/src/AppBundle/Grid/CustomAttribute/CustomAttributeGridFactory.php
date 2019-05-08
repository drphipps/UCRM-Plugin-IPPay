<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\CustomAttribute;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Controller\CustomAttributeController;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\CustomAttribute;

class CustomAttributeGridFactory
{
    /**
     * @var GridFactory
     */
    private $gridFactory;

    /**
     * @var CustomAttributeDataProvider
     */
    private $customAttributeDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        CustomAttributeDataProvider $customAttributeDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->customAttributeDataProvider = $customAttributeDataProvider;
    }

    public function create(): Grid
    {
        $grid = $this->gridFactory->createGrid($this->customAttributeDataProvider->getGridModel(), __CLASS__);
        $grid->addIdentifier('a_id', 'a.id');
        $grid->setDefaultSort('a_id');
        $grid->setRowUrl('custom_attribute_show');

        $grid->attached();

        $grid->addTextColumn('a_name', 'a.name', 'Name')->setSortable();

        $grid->addCustomColumn(
            'a_attributeType',
            'Attribute type',
            function ($row) {
                return CustomAttribute::ATTRIBUTE_TYPES[$row['a_attributeType']] ?? BaseColumn::EMPTY_COLUMN;
            }
        )
            ->setSortable();

        $grid->addEditActionButton('custom_attribute_edit', [], CustomAttributeController::class);
        $grid->addDeleteActionButton('custom_attribute_delete', [], CustomAttributeController::class);

        return $grid;
    }
}
