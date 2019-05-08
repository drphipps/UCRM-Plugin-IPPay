<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\QueryString;
use Nette\Utils\Strings;

abstract class BaseQueryFactory implements QueryFactoryInterface
{
    private const FUZZY_ANALYZERS = [
        'custom_standard_analyzer',
    ];

    protected function buildQueryString(string $term, array $fields, string $analyzer): QueryString
    {
        $queryString = new QueryString();
        $queryString->setFields($fields);
        if (in_array($analyzer, self::FUZZY_ANALYZERS, true)) {
            $term = $this->makeTermFuzzy($term);
        }
        $queryString->setQuery($term);
        $queryString->setAnalyzer($analyzer);
        $queryString->setAnalyzeWildcard();
        $queryString->setAllowLeadingWildcard(false);
        $queryString->setParam('lenient', true);
        $queryString->setParam('fuzziness', 2);
        $queryString->setParam('fuzzy_prefix_length', 1);
        $queryString->setParam('phrase_slop', 1);

        return $queryString;
    }

    /**
     * Custom implementation of Elastica\Util::escapeTerm - we want to allow some of the characters.
     */
    protected function escapeTerm(string $term): string
    {
        $result = $term;
        $chars = ['\\', '&&', '||', '(', ')', '{', '}', '[', ']', '^', '~', ':', '/', '<', '>'];

        foreach ($chars as $char) {
            $result = str_replace($char, '\\' . $char, $result);
        }

        return $result;
    }

    private function makeTermFuzzy(string $term): string
    {
        return Strings::replace($term, '~\b( +)|$~', '~$1');
    }
}
