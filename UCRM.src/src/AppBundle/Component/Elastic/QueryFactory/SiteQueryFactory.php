<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\AbstractQuery;

class SiteQueryFactory extends BaseQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        return $this->buildQueryString(
            $this->escapeTerm($term),
            [
                'name^10',
                'address',
                'notes^5',
            ],
            'custom_standard_analyzer'
        );
    }
}
