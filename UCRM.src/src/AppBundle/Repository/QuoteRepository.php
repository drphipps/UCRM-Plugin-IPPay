<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\Financial\QuoteItemService;
use AppBundle\Entity\Service;
use AppBundle\Util\Arrays;

class QuoteRepository extends BaseRepository
{
    /**
     * @return Quote[]
     */
    public function getExportableByIds(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('i')
            ->addSelect('cc')
            ->leftJoin('i.currency', 'cc')
            ->where('i.id IN (:ids)')
            ->setParameter('ids', $ids);

        $quotes = $qb->getQuery()->getResult();

        Arrays::sortByArray($quotes, $ids, 'id');

        $this->loadRelatedEntities('quoteItems', $ids);

        return $quotes;
    }

    /**
     * @return Quote[]
     */
    public function getServiceQuotes(Service $service): array
    {
        $quotes = $this->createQueryBuilder('q')
            ->select('q')
            ->join('q.quoteItems', 'qi')
            ->leftJoin(QuoteItemService::class, 'qis', 'WITH', 'qis.id = qi.id')
            ->leftJoin(QuoteItemFee::class, 'qif', 'WITH', 'qif.id = qi.id')
            ->leftJoin('qif.fee', 'f')
            ->andWhere('qis.service = :service OR f.service = :service')
            ->setParameter('service', $service)
            ->orderBy('q.createdDate', 'DESC')
            ->addOrderBy('q.id', 'DESC')
            ->getQuery()
            ->getResult();

        $quoteIds = array_map(
            function (Quote $quote) {
                return $quote->getId();
            },
            $quotes
        );

        $this->loadRelatedEntities('quoteItems', $quoteIds);

        return $quotes;
    }
}
