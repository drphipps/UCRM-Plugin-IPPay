<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\Export\ExportPathData;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\Request\QuoteCollectionRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class QuoteDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    public function __construct(EntityManagerInterface $entityManager, PdfHandler $pdfHandler)
    {
        $this->entityManager = $entityManager;
        $this->pdfHandler = $pdfHandler;
    }

    public function getGridModel(Client $client = null): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->addSelect('q.quoteNumber as q_quote_number')
            ->addSelect('q.status as q_status')
            ->addSelect('cc, o.name as o_name')
            ->addSelect('q.total as q_total')
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->join('q.currency', 'cc')
            ->join('q.organization', 'o')
            ->join('q.client', 'c')
            ->join('c.user', 'u')
            ->leftJoin('q.quoteItems', 'qi')
            ->orderBy('q.id')
            ->groupBy('q.id, cc.id, o.id, c.id, u.id');

        if ($client) {
            $queryBuilder
                ->andWhere('q.client = :clientId')
                ->setParameter('clientId', $client->getId());
        }

        return $queryBuilder;
    }

    /**
     * @return Quote[]
     */
    public function getQuotes(QuoteCollectionRequest $request): array
    {
        $quoteRepository = $this->entityManager->getRepository(Quote::class);
        $qb = $quoteRepository
            ->createQueryBuilder('q');

        if ($request->client) {
            $qb->andWhere('q.client = :client')
                ->setParameter('client', $request->client);
        }

        if ($request->statuses) {
            $qb->andWhere('q.status IN (:statuses)')
                ->setParameter('statuses', $request->statuses);
        }

        if ($request->limit !== null) {
            $qb->setMaxResults($request->limit);
        }

        if ($request->offset !== null) {
            $qb->setFirstResult($request->offset);
        }

        if ($request->number !== null) {
            $qb->andWhere('q.quoteNumber = :number')
                ->setParameter('number', $request->number);
        }

        if ($request->startDate) {
            $qb->andWhere('q.createdDate >= :startDate')
                ->setParameter('startDate', $request->startDate, UtcDateTimeType::NAME);
        }

        if ($request->endDate) {
            $qb->andWhere('q.createdDate <= :endDate')
                ->setParameter('endDate', $request->endDate, UtcDateTimeType::NAME);
        }

        $orderBy = [
            $request->order ?: 'createdDate' => $request->direction ?: 'ASC',
        ];

        if (in_array($request->order, ['clientFirstName', 'clientLastName'], true)) {
            array_merge(
                $orderBy,
                [
                    'clientCompanyName' => $request->direction,
                ]
            );
        }
        $orderBy = array_merge($orderBy, ['id' => $request->direction ?: 'ASC']);

        foreach ($orderBy as $column => $direction) {
            $qb->addOrderBy(sprintf('q.%s', $column), $direction);
        }

        $quotes = $qb->getQuery()->getResult();

        $quoteIds = array_map(
            function (Quote $quote) {
                return $quote->getId();
            },
            $quotes
        );

        $quoteRepository->loadRelatedEntities('quoteItems', $quoteIds);

        return $quotes;
    }

    /**
     * @return ExportPathData[]
     */
    public function getAllQuotePdfPathsForClient(Client $client): array
    {
        $request = new QuoteCollectionRequest();
        $request->client = $client;
        $quotes = $this->getQuotes($request);

        $paths = [];
        foreach ($quotes as $quote) {
            $path = $this->pdfHandler->getFullQuotePdfPath($quote);
            if (! $path) {
                continue;
            }

            $paths[] = new ExportPathData(sprintf('%s.pdf', $quote->getQuoteNumber()), $path);
        }

        return $paths;
    }
}
