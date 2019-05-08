<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Elastic;

use Elastica\Multi\Search as MSearch;

class MultiSearch extends BaseSearch
{
    private const RESULT_LIMIT = 5;

    private const SEARCHABLE_TYPES = [
        self::TYPE_CLIENT,
        self::TYPE_DEVICE,
        self::TYPE_INVOICE,
        self::TYPE_QUOTE,
        self::TYPE_PAYMENT,
        self::TYPE_TICKET,
        self::TYPE_NAVIGATION,
        self::TYPE_HELP,
    ];

    /**
     * Types, that are not entities and have to be transformed manually.
     */
    private const RAW_TYPES = [
        self::TYPE_NAVIGATION,
        self::TYPE_HELP,
    ];

    private const LOCALE_TYPES = [
        self::TYPE_NAVIGATION,
    ];

    public const CATEGORIES = [
        self::TYPE_CLIENT => 'Clients',
        self::TYPE_DEVICE => 'Devices',
        self::TYPE_INVOICE => 'Invoices',
        self::TYPE_QUOTE => 'Quotes',
        self::TYPE_PAYMENT => 'Payments',
        self::TYPE_TICKET => 'Tickets',
        self::TYPE_NAVIGATION => 'Settings',
        self::TYPE_HELP => 'Help',
    ];

    /**
     * @var MultiSearchSerializer
     */
    private $multiSearchSerializer;

    public function setMultiSearchSerializer(MultiSearchSerializer $multiSearchSerializer): void
    {
        $this->multiSearchSerializer = $multiSearchSerializer;
    }

    public function search(string $term, array $types = self::SEARCHABLE_TYPES): array
    {
        $multiSearch = new MSearch($this->elasticClient);

        foreach ($types as $type) {
            if (! $this->isAllowed($type)) {
                continue;
            }

            if (in_array($type, self::LOCALE_TYPES, true)) {
                $elasticType = $this->getLocaleType($type);
            } else {
                $elasticType = $this->getType($type);
            }

            $search = $elasticType->createSearch(
                $this->queryFactories[$type]->create($term, true),
                self::RESULT_LIMIT
            );
            $multiSearch->addSearch($search, $type);
        }

        $results = [];
        $resultSet = $multiSearch->search();

        foreach ($resultSet->getResultSets() as $type => $res) {
            if (in_array($type, self::RAW_TYPES, true)) {
                $results[$type] = $res->getResults();
            } elseif (array_key_exists($type, $this->transformers)) {
                $results[$type] = $this->transformers[$type]->hybridTransform($res->getResults());
            }
        }

        return $this->multiSearchSerializer->serializeResults($results);
    }
}
