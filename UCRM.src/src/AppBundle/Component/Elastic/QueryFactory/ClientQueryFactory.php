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

class ClientQueryFactory extends BaseQueryFactory
{
    public function create(string $term, bool $isMultiSearch = false): AbstractQuery
    {
        $term = $this->escapeTerm($term);
        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'id^10',
                    'userIdent^10',
                ],
                'keyword_analyzer'
            )
        );

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'nameForView^10',
                    'street1^5',
                    'street2',
                    'city^5',
                    'note^5',
                    'companyContactNameForView^5',
                ],
                'custom_standard_analyzer'
            )
        );
        $boolQuery->addShould($this->buildNestedServices($term));
        if ($nested = $this->buildUnassignedServiceIps($term)) {
            $boolQuery->addShould($nested);
        }
        $boolQuery->addShould($this->buildNestedInvoices($term));
        $boolQuery->addShould($this->buildNestedContacts($term));

        return $boolQuery;
    }

    private function buildNestedServices(string $term): Nested
    {
        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('notDeletedServices');

        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'notDeletedServices.name',
                    'notDeletedServices.note',
                ],
                'custom_standard_analyzer'
            )
        );

        $ip = Strings::replace($term, '~\\\(\/[0-9]+)~', '$1');
        if (IpRangeParser::isSingleOrCidrIpAddress($ip)) {
            $nestedIps = new Nested();
            $nestedIps->setScoreMode('sum');
            $nestedIps->setPath('notDeletedServices.serviceIps');
            $termQuery = new Term();
            $termQuery->setTerm('notDeletedServices.serviceIps.ipRange.ipAddressString', $ip, 100.0);
            $nestedIps->setQuery($termQuery);
            $boolQuery->addShould($nestedIps);
        }

        $nested->setQuery($boolQuery);

        return $nested;
    }

    private function buildUnassignedServiceIps(string $term): ?Nested
    {
        $ip = Strings::replace($term, '~\\\(\/[0-9]+)~', '$1');
        if (! IpRangeParser::isSingleOrCidrIpAddress($ip)) {
            return null;
        }

        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('unassignedServiceIps');
        $termQuery = new Term();
        $termQuery->setTerm('unassignedServiceIps.ipRange.ipAddressString', $ip, 100.0);
        $nested->setQuery($termQuery);

        return $nested;
    }

    private function buildNestedInvoices(string $term): Nested
    {
        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('invoices');

        $queryString = $this->buildQueryString(
            $term,
            [
                'invoices.invoiceNumber^10',
            ],
            'keyword_analyzer'
        );
        $nested->setQuery($queryString);

        return $nested;
    }

    private function buildNestedContacts(string $term): Nested
    {
        $nested = new Nested();
        $nested->setScoreMode('sum');
        $nested->setPath('contacts');

        $boolQuery = new BoolQuery();

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'contacts.email^10',
                ],
                'email_analyzer'
            )
        );

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'contacts.phone^5',
                ],
                'custom_standard_analyzer'
            )
        );

        $boolQuery->addShould(
            $this->buildQueryString(
                $term,
                [
                    'contacts.name^5',
                ],
                'custom_standard_analyzer'
            )
        );

        $nested->setQuery($boolQuery);

        return $nested;
    }
}
