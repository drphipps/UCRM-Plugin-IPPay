<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use Elastica\Result;

class Search extends BaseSearch
{
    public function search(string $type, string $term, bool $onlyIds = false, ?int $limit = null): array
    {
        if (! $this->isAllowed($type)) {
            return [];
        }

        $elasticType = $this->getType($type);
        $query = $this->queryFactories[$type]->create($term);

        $resultSet = $elasticType->search($query, $limit ?? $elasticType->count());
        if ($onlyIds) {
            return array_map(
                function (Result $result) {
                    return $result->getId();
                },
                $resultSet->getResults()
            );
        }

        if (! array_key_exists($type, $this->transformers)) {
            throw new \InvalidArgumentException(
                sprintf('Transformer does not exist for type "%s".', $type)
            );
        }

        return $this->transformers[$type]->transform($resultSet->getResults());
    }
}
