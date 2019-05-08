<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;

class TicketQueryFactory extends BaseQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'subject^10',
                    'client.nameForView^5',
                ],
                'custom_standard_analyzer'
            )
        );

        if (! $isMultiSearch) {
            $boolQuery->addShould($this->buildNestedComments($term));
        }

        return $boolQuery;
    }

    private function buildNestedComments(string $term): Nested
    {
        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('comments');

        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'comments.body',
                ],
                'custom_standard_analyzer'
            )
        );

        $nested->setQuery($boolQuery);

        return $nested;
    }
}
