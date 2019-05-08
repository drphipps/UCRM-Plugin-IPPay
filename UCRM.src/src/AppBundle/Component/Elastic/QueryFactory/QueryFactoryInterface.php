<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic\QueryFactory;

use Elastica\Query\AbstractQuery;

interface QueryFactoryInterface
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery;
}
