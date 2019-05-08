<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\ClientZone;

use AppBundle\Component\Grid\Grid;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Grid\Quote\BaseQuoteGridFactory;
use AppBundle\Util\Formatter;
use Doctrine\ORM\QueryBuilder;

class QuoteGridFactory extends BaseQuoteGridFactory
{
    public function create(Client $client): Grid
    {
        $qb = $this->quoteDataProvider->getGridModel($client);
        $grid = $this->gridFactory->createGrid($qb, __CLASS__, 'i_id');
        $grid->setRowUrl('client_zone_quote_show');
        $grid->addIdentifier('q_id', 'q.id');
        $grid->setDefaultSort('q_created_date', Grid::DESC);

        $grid->attached();

        $grid
            ->addRawCustomColumn(
                'q_quote_number',
                'Quote number',
                function ($row) {
                    return sprintf(
                        '%s%s',
                        htmlspecialchars($row['q_quote_number'] ?? '', ENT_QUOTES),
                        $this->badgeFactory->createQuoteStatusBadge($row['q_status'])
                    );
                }
            )
            ->setSortable();

        $grid
            ->addCustomColumn(
                'q_total',
                'Total',
                function ($row) {
                    /** @var Quote $quote */
                    $quote = $row[0];

                    return $this->formatter->formatCurrency(
                        $row['q_total'],
                        $quote->getCurrency()->getCode(),
                        $quote->getOrganization()->getLocale()
                    );
                }
            )
            ->setSortable()
            ->setAlignRight();

        $grid
            ->addTwigFilterColumn(
                'q_created_date',
                'q.createdDate',
                'Created date',
                'localizedDate',
                [Formatter::DEFAULT, Formatter::NONE]
            )
            ->setSortable()
            ->setOrderByCallback(
                function (QueryBuilder $model, string $direction) {
                    $model->orderBy('q.createdDate', $direction);
                    $model->addOrderBy('q.id', $direction);
                }
            );

        return $grid;
    }
}
