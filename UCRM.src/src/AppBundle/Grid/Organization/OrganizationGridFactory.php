<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Organization;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\OrganizationController;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Security\Permission;
use Symfony\Component\Translation\TranslatorInterface;

class OrganizationGridFactory
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
     * @var OrganizationFacade
     */
    private $organizationFacade;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        OrganizationFacade $organizationFacade,
        TranslatorInterface $translator
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->organizationFacade = $organizationFacade;
        $this->translator = $translator;
    }

    public function create(): Grid
    {
        $qb = $this->organizationFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setDefaultSort('o_name');
        $grid->addIdentifier('o_id', 'o.id');
        $grid->setRowUrl('organization_show');
        $grid->addIdentifier('o_selected', 'o.selected');

        $grid->attached();

        $grid->addTextColumn('o_name', 'o.name', 'Name')
            ->setSortable();
        $grid->addTextColumn('clients_count', 'COUNT(c.id)', 'Clients')
            ->setSortable();

        $defaultButton = $grid->addActionButton(
            'organization_default',
            [],
            OrganizationController::class,
            Permission::EDIT
        );
        $defaultButton->setTitle($this->gridHelper->trans('Default'));
        $defaultButton->setCssClasses(
            [
                'js-organization-default',
            ]
        );
        $defaultButton->addRenderCondition(
            function ($row) {
                return ! $row['o_selected'];
            }
        );

        $defaultSelectedButton = $grid->addActionButton(
            'organization_default',
            [],
            OrganizationController::class,
            Permission::EDIT
        );
        $defaultSelectedButton->setTitle($this->gridHelper->trans('Default'));
        $defaultSelectedButton->setCssClasses(
            [
                'js-organization-default',
                'button--primary-o',
            ]
        );
        $defaultSelectedButton->addRenderCondition(
            function ($row) {
                return $row['o_selected'];
            }
        );

        $grid->addEditActionButton('organization_edit', [], OrganizationController::class);

        $deleteButton = $grid->addDeleteActionButton('organization_delete', [], OrganizationController::class);
        $deleteButton->addDisabledCondition(
            function ($row) {
                return (int) $row['clients_count'] > 0;
            }
        );
        $deleteButton->setDisabledTooltip(
            $this->translator->trans('You cannot delete organization with related clients.')
        );

        return $grid;
    }
}
