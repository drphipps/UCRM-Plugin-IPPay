<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\MultiMatch;
use Elastica\Query\Nested;

class NavigationQueryFactory extends BaseLocaleQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $analyzer = $this->getAnalyzerFromLocale();
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        // MultiMatch query is used here to allow fuzziness and analysis of searched term.
        // Does not support QueryString's wildcards, exact matches with quotes, etc.
        $match = new MultiMatch();
        $match->setFields(
            [
                'heading^2',
                'path',
            ]
        );
        $match->setAnalyzer($analyzer);
        $match->setFuzziness(2.0);
        $match->setType(MultiMatch::TYPE_BEST_FIELDS);
        $match->setQuery($term);
        $boolQuery->addShould($match);
        $boolQuery->addShould($this->buildNestedLabels($term, $analyzer));

        return $boolQuery;
    }

    private function buildNestedLabels(string $term, string $analyzer): Nested
    {
        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('labels');

        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'labels.label',
                ],
                $analyzer
            )
        );

        $nested->setQuery($boolQuery);

        return $nested;
    }
}
