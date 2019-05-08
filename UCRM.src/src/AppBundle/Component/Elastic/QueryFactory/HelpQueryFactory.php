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

class HelpQueryFactory extends BaseQueryFactory
{
    /**
     * @var string
     */
    private $language;

    public function __construct(string $analyzer)
    {
        $this->language = $analyzer;
    }

    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        // MultiMatch query is used here to allow fuzziness and analysis of searched term.
        // Does not support QueryString's wildcards, exact matches with quotes, etc.
        $match = new MultiMatch();
        $match->setFields(
            [
                'helpName^2',
                'helpText',
            ]
        );
        $match->setAnalyzer($this->language);
        $match->setFuzziness(2.0);
        $match->setType(MultiMatch::TYPE_BEST_FIELDS);
        $match->setQuery($term);
        $boolQuery->addShould($match);

        return $boolQuery;
    }
}
