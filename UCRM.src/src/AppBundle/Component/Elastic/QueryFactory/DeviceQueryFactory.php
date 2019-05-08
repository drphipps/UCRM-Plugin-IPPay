<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use AppBundle\Util\IpRangeParser;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;
use Elastica\Query\Term;
use Nette\Utils\Strings;

class DeviceQueryFactory extends BaseQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'name^10',
                    'modelName^5',
                    'site.name',
                    'vendor.name',
                    'notes^5',
                ],
                'custom_standard_analyzer'
            )
        );

        $nestedInterfaces = $this->buildNestedInterfaces($term);
        if ($nestedInterfaces) {
            $boolQuery->addShould($nestedInterfaces);
        }

        return $boolQuery;
    }

    private function buildNestedInterfaces(string $term)
    {
        $ip = Strings::replace($term, '~\\\(\/[0-9]+)~', '$1');
        if (! IpRangeParser::isSingleOrCidrIpAddress($ip)) {
            return null;
        }

        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('interfaces');

        $nestedIps = new Nested();
        $nestedIps->setScoreMode('sum');
        $nestedIps->setPath('interfaces.interfaceIps');
        $termQuery = new Term();
        $termQuery->setTerm('interfaces.interfaceIps.ipRange.ipAddressString', $ip, 100.0);
        $nestedIps->setQuery($termQuery);

        $nested->setQuery($nestedIps);

        return $nested;
    }
}
