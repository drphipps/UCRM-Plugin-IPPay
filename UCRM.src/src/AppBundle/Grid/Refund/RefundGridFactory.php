<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Refund;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\RefundController;
use AppBundle\DataProvider\RefundDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Refund;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Facade\RefundFacade;
use AppBundle\Security\Permission;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RefundGridFactory
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
     * @var RefundDataProvider
     */
    private $refundDataProvider;

    /**
     * @var RefundFacade
     */
    private $refundFacade;

    /**
     * @var OrganizationFacade
     */
    private $organizationFacade;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        RefundDataProvider $refundDataProvider,
        RefundFacade $refundFacade,
        OrganizationFacade $organizationFacade
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->refundDataProvider = $refundDataProvider;
        $this->refundFacade = $refundFacade;
        $this->organizationFacade = $organizationFacade;
    }

    public function create(Client $client = null): Grid
    {
        $qb = $this->refundDataProvider->getGridModel($client);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__ . ($client ? $client->getId() : ''));
        $grid->addIdentifier('r_id', 'r.id');
        $grid->setDefaultSort('r_created_date', Grid::DESC);
        $grid->setRowUrl('refund_show');

        if ($client) {
            $grid->addRouterUrlParam('id', $client->getId());
        }

        $grid->attached();

        $grid
            ->addTwigFilterColumn(
                'r_created_date',
                'r.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('r.createdDate', $direction);
                    $model->addOrderBy('r.id', $direction);
                }
            );

        $grid
            ->addCustomColumn(
                'r_method',
                'Method',
                function ($row) {
                    if (! array_key_exists($row['r_method'], Refund::METHOD_TYPE)) {
                        return BaseColumn::EMPTY_COLUMN;
                    }

                    return $this->gridHelper->trans(Refund::METHOD_TYPE[$row['r_method']]);
                }
            )
            ->setSortable();

        if (! $client) {
            $grid
                ->addCustomColumn(
                    'c_fullname',
                    'Client',
                    function ($row) {
                        return $row['c_fullname'] ?: BaseColumn::EMPTY_COLUMN;
                    }
                )
                ->setSortable();
        }

        $grid
            ->addCustomColumn(
                'r_amount',
                'Amount',
                function ($row) {
                    /** @var Refund $refund */
                    $refund = $row[0];

                    return $this->formatter->formatCurrency(
                        $refund->getAmount(),
                        $refund->getCurrency() ? $refund->getCurrency()->getCode() : null,
                        $refund->getClient()->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        if (! $client) {
            $organizations = $this->organizationFacade->findAllForm();
            if (count($organizations) > 1) {
                $grid->addCustomColumn(
                    'o_name',
                    'Organization',
                    function ($row) {
                        return $row['o_name'] ?: BaseColumn::EMPTY_COLUMN;
                    }
                );

                $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
            }
        }

        if (! $client || ! $client->isDeleted()) {
            $deleteMultiAction = $grid->addMultiAction(
                'delete',
                'Delete',
                function () use ($grid) {
                    return $this->multiDeleteAction($grid);
                },
                [
                    'button--danger',
                ],
                'Do you really want to delete these refunds?',
                null,
                'ucrm-icon--trash'
            );
            $deleteMultiAction->confirmOkay = 'Delete forever';
        }

        return $grid;
    }

    private function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, RefundController::class);

        list($deleted, $failed) = $this->refundFacade->handleDeleteMultipleByIds($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% refunds.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% refunds could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }
}
