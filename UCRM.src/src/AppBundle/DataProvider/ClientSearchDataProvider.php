<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\Elastic\Search;
use AppBundle\Entity\Client;
use AppBundle\Exception\ElasticsearchException;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\ResponseException;

class ClientSearchDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Search
     */
    private $elasticsearch;

    public function __construct(EntityManagerInterface $entityManager, Search $elasticsearch)
    {
        $this->entityManager = $entityManager;
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Returns an array of result or null in case of error.
     */
    public function getClients(string $query): ?array
    {
        try {
            $ids = $this->elasticsearch->search(Search::TYPE_CLIENT, $query, true);
        } catch (ResponseException | ConnectionException | ElasticsearchException $exception) {
            return null;
        }

        if (! $ids) {
            return [];
        }

        return $this->entityManager
            ->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->select(
                [
                    'c.id',
                    'u.firstName',
                    'u.lastName',
                    'c.companyName',
                ]
            )
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
