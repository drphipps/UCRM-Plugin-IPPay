<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Tariff;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\TariffController;
use AppBundle\Entity\Tariff;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Facade\TariffFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Nette\Utils\Html;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TariffGridFactory
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
     * @var TariffFacade
     */
    private $tariffFacade;

    /**
     * @var OrganizationFacade
     */
    private $organizationFacade;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        TariffFacade $tariffFacade,
        OrganizationFacade $organizationFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->tariffFacade = $tariffFacade;
        $this->organizationFacade = $organizationFacade;
    }

    public function create(): Grid
    {
        $qb = $this->tariffFacade->getGridModel();

        $grid = $this->gridFactory->createGrid($qb, __CLASS__);
        $grid->setPostFetchCallback($this->tariffFacade->getGridPostFetchCallback());
        $grid->setDefaultSort('t_name');
        $grid->addIdentifier('t_id', 't.id');
        $grid->setRowUrl('tariff_show');

        $grid->attached();

        $grid->addTextColumn('t_name', 't.name', 'Name')->setSortable();
        $grid->addTextColumn('t_count', 'COUNT(DISTINCT s)', 'Active services')->setSortable();

        $organizations = $this->organizationFacade->findAllForm();
        $showOrganizations = count($organizations) > 1;
        if ($showOrganizations) {
            $grid->addTextColumn('o_name', 'o.name', 'Organization')->setSortable();
        }

        $periods = TariffPeriod::PERIOD_REPLACE_STRING;
        foreach ($periods as $period => $label) {
            $grid
                ->addCustomColumn(
                    sprintf('tp_%d', $period),
                    $label,
                    function ($row) use ($period) {
                        /** @var Tariff $tariff */
                        $tariff = $row[0];
                        $tariffPeriod = $tariff->getPeriodByPeriod($period);

                        if ($tariffPeriod && $tariffPeriod->isEnabled()) {
                            return $this->formatter->formatCurrency(
                                $tariffPeriod->getPrice(),
                                $row['currencyCode']
                            );
                        }

                        return BaseColumn::EMPTY_COLUMN;
                    }
                )
                ->setWidth(12);
        }

        if ($showOrganizations) {
            $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
        }

        $grid->addEditActionButton('tariff_edit', [], TariffController::class);

        $el = Html::el()
            ->addHtml(
                Html::el('p')
                    ->setAttribute('class', 'verticalRhythmQuarter')
                    ->setText($this->gridHelper->trans('Do you really want to delete this item?'))
            )
            ->addHtml(
                Html::el('small')
                    ->setAttribute('class', 'warning')
                    ->setText(
                        $this->gridHelper->trans(
                            'Existing services will be unaffected but the service plan won\'t be available for new services.'
                        )
                    )
            );

        $deleteMultiAction = $grid->addMultiAction(
            'delete',
            'Delete',
            function () use ($grid) {
                return $this->multiDeleteAction($grid);
            },
            [
                'button--danger',
            ],
            $el,
            null,
            'ucrm-icon--trash'
        );
        $deleteMultiAction->confirmOkay = 'Delete forever';

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, TariffController::class);

        list($deleted, $failed) = $this->tariffFacade->handleDeleteMultiple($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% service plans.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% service plans could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
