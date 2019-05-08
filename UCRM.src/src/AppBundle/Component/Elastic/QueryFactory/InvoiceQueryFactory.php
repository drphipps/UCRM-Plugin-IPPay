<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;

class InvoiceQueryFactory extends BaseQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'invoiceNumber^10',
                ],
                'keyword_analyzer'
            )
        );

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'client.nameForView',
                    'notes',
                    'comment',
                ],
                'custom_standard_analyzer'
            )
        );

        return $boolQuery;
    }
}
