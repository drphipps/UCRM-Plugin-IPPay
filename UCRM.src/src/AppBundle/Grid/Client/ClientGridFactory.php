<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Client;

use AppBundle\Component\Elastic;
use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Filter\MultipleSelectFilterField;
use AppBundle\Component\Grid\Filter\SelectFilterField;
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\ClientController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Security\SpecialPermission;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Html;

class ClientGridFactory extends AbstractClientGridFactory
{
    public function create(string $filterType): Grid
    {
        $qb = $this->clientDataProvider->getGridModel(
            $filterType === ClientController::FILTER_ARCHIVE,
            $filterType === ClientController::FILTER_LEAD
        );

        $grid = $this->gridFactory->createGrid($qb, __CLASS__ . $filterType, 'c_id');
        $grid->setRowUrl('client_show');
        $grid->addRouterUrlParam('filterType', $filterType);
        $grid->addIdentifier('c_id', 'c.id');
        $grid->addIdentifier('c_deletedAt', 'c.deletedAt');
        $grid->setPostFetchCallback($this->clientDataProvider->getGridPostFetchCallback());
        $grid->attached();

        if ($this->gridHelper->getOption(Option::CLIENT_ID_TYPE) === Option::CLIENT_ID_TYPE_DEFAULT) {
            $grid
                ->addTextColumn('c_clientId', 'c.id', 'ID')
                ->setWidth(8)
                ->setSortable()
                ->setIsGrouped();
        } else {
            $grid
                ->addTextColumn('c_customId', 'c.userIdent', 'Custom ID')
                ->setWidth(8)
                ->setSortable()
                ->setOrderByCallback(
                    function (QueryBuilder $model, string $direction) {
                        $model->orderBy('c.userIdentInt', $direction);
                        $model->addOrderBy('c.userIdent', $direction);
                    }
                )
                ->setIsGrouped();
        }

        $grid
            ->addRawCustomColumn(
                'c_fullname',
                'Name',
                function ($row) {
                    return $this->renderClientNameWithBadges($row[0]);
                }
            )
            ->setWidth(25)
            ->setSortable();

        if ($this->gridHelper->isSpecialPermissionGranted(SpecialPermission::CLIENT_ACCOUNT_STANDING)) {
            $grid
                ->addRawCustomColumn(
                    'c_balance',
                    'Balance',
                    function ($row) {
                        /** @var Client $client */
                        $client = $row[0];

                        return $this->renderClientBalance($client, $row['currencyCode']);
                    }
                )
                ->setWidth(12)
                ->setSortable()
                ->setAlignRight();
        }

        $grid
            ->addCustomColumn(
                'tariffs',
                'Service plans',
                function ($row) {
                    return $row['tariffs'] ?: BaseColumn::EMPTY_COLUMN;
                }
            )
            ->setSortable();

        $grid
            ->addRawCustomColumn(
                'devices',
                'Connected to',
                function ($row) {
                    return $this->renderSiteDevice($row['devices'] ?? '');
                }
            )
            ->setSortable();

        $organizations = $this->organizationFacade->findAllForm();
        $showOrganizations = count($organizations) > 1;
        if ($showOrganizations) {
            $grid->addCustomColumn(
                'o_name',
                'Organization',
                function ($row) {
                    return $row['o_name'] ?: BaseColumn::EMPTY_COLUMN;
                }
            );
        }

        $grid
            ->addEditActionButton('client_edit', [], ClientController::class)
            ->addRenderCondition(
                function ($row) {
                    return ! $row['c_deletedAt'];
                }
            );

        $tooltip = sprintf(
            '%s%s',
            $this->gridHelper->trans('Search by name, email, phone, address, invoices, services or IP address'),
            html_entity_decode('&hellip;')
        );
        $grid->addElasticFilter(
            'search',
            'c.id',
            $tooltip,
            Elastic\Search::TYPE_CLIENT,
            $this->gridHelper->trans('Search')
        );

        if ($filterType === ClientController::FILTER_ARCHIVE) {
            $deleteMultiAction = $grid->addMultiAction(
                'delete',
                'Delete',
                function () use ($grid) {
                    return $this->multiDeleteAction($grid);
                },
                [
                    'button--danger',
                ],
                'Do you really want to permanently delete these clients?',
                null,
                'ucrm-icon--trash'
            );
            $deleteMultiAction->confirmOkay = 'Delete forever';
        } else {
            $tags = [
                'overdue' => [
                    'label' => $this->gridHelper->trans('Overdue'),
                    'column' => 'c.hasOverdueInvoice',
                    'attributes' => [
                        'data-status-class' => 'overdue',
                    ],
                ],
                'suspended' => [
                    'label' => $this->gridHelper->trans('Suspended'),
                    'column' => 'c.hasSuspendedService',
                    'attributes' => [
                        'data-status-class' => 'danger',
                    ],
                ],
                'outage' => [
                    'label' => $this->gridHelper->trans('Outage'),
                    'column' => 'c.hasOutage',
                    'attributes' => [
                        'data-status-class' => 'warning',
                    ],
                ],
            ];
            $usedTags = $this->clientTagDataProvider->getUsedTagsFilter();
            if ($usedTags) {
                $tags = $tags
                    + [
                        SelectFilterField::OPTION_SEPARATOR => [
                            'label' => '-------------------',
                        ],
                    ]
                    + $usedTags;
            }

            $tagsFilter = $grid->addMultipleSelectFilter(
                'tags',
                '',
                'Tags',
                $tags,
                $this->gridHelper->trans('Make a choice.')
            );
            $tagsFilter->setOverrideCssClasses('select2-tag-filter');
            $tagsFilter->setFilterCallback(
                function (QueryBuilder $model, $value, MultipleSelectFilterField $filter) {
                    $filterOptions = $filter->getOptions();

                    foreach ($value as $val) {
                        if (is_numeric($val)) {
                            $model
                                ->andWhere(sprintf(':tagId%d MEMBER OF c.clientTags', $val))
                                ->setParameter(sprintf('tagId%d', $val), $val);
                        } elseif (array_key_exists($val, $filterOptions)) {
                            $model->andWhere(sprintf('%s = TRUE', $filterOptions[$val]['column']));
                        }
                    }
                }
            );

            $archiveMultiAction = $grid->addMultiAction(
                'archive',
                'Archive',
                function () use ($grid) {
                    return $this->multiArchiveAction($grid);
                },
                [
                    'button--danger',
                ],
                $this->createConfirmArchiveMessage(),
                null,
                'ucrm-icon--archive'
            );
            $archiveMultiAction->confirmOkay = 'Archive';
            $archiveMultiAction->confirmTitle = 'Archive clients';
        }

        $grid->addMultiAction(
            'export-csv',
            'Export',
            function () use ($grid) {
                return $this->exportCsvAction($grid);
            },
            [
                'button--primary',
            ],
            null,
            'Exports filtered clients into CSV file.',
            'ucrm-icon--export',
            true
        );

        if ($showOrganizations) {
            $grid->addSelectFilter('organization', 'o.id', 'Organization', $organizations);
        }

        return $grid;
    }

    private function createConfirmArchiveMessage(): string
    {
        $el = Html::el()
            ->addHtml(
                Html::el('p')
                    ->setAttribute('class', 'verticalRhythmQuarter')
                    ->setText($this->gridHelper->trans('Do you really want to archive these clients?'))
            );

        if (
            $this->gridHelper->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            || $this->gridHelper->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            $el->addHtml(
                Html::el('small')
                    ->setAttribute('class', 'warning')
                    ->setText(
                        $this->gridHelper->trans('Related payment subscriptions will be automatically canceled.')
                    )
            );
        }

        return (string) $el;
    }
}
