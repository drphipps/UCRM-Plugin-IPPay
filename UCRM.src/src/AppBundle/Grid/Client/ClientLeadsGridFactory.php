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
use AppBundle\Component\Grid\Grid;
use AppBundle\Controller\ClientController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Option;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class ClientLeadsGridFactory extends AbstractClientGridFactory
{
    public function create(): Grid
    {
        $qb = $this->clientDataProvider->getGridModel(false, true);

        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'c_id');
        $grid->setRowUrl('client_show');
        $grid->addRouterUrlParam('filterType', ClientController::FILTER_LEAD);
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

        $grid
            ->addTwigFilterColumn(
                'c_createdDate',
                'u.createdAt',
                'Created',
                'localizedDateToday',
                [Formatter::NONE, Formatter::SHORT, Formatter::DEFAULT, Formatter::SHORT]
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'tariffs',
                'Quoted services',
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

        $grid->addRawCustomColumn(
            'note',
            'Note',
            function ($row) {
                /** @var Client $client */
                $client = $row[0];
                $note = $client->getNote();

                if (empty($note)) {
                    return BaseColumn::EMPTY_COLUMN;
                }

                $span = Html::el(
                    'span',
                    [
                        'class' => 'appType--quiet',
                    ]
                );
                $span->setText($note);

                if (Strings::length($note) > 80) {
                    $span->addAttributes(
                        [
                            'data-tooltip' => $note,
                        ]
                    );
                    $span->setText(Strings::truncate($note, 80));
                }

                return (string) $span;
            }
        );

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

        $tags = $this->clientTagDataProvider->getUsedTagsFilter(true);
        if ($tags) {
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
        }

        $grid->addMultiAction(
            'archive',
            'Archive',
            function () use ($grid) {
                return $this->multiArchiveAction($grid);
            },
            [
                'button--danger',
            ],
            'Do you really want to archive these clients?',
            null,
            'ucrm-icon--archive'
        );

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
}
