<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\ClientLogCollectionRequest;
use AppBundle\Entity\ClientLog;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

class ClientLogDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return ClientLog[]
     */
    public function getAllLogs(ClientLogCollectionRequest $request): array
    {
        $repository = $this->em->getRepository(ClientLog::class);
        $criteria = Criteria::create();

        if ($request->client) {
            $criteria->andWhere(Criteria::expr()->eq('client', $request->client));
        }

        if ($request->startDate) {
            $criteria->andWhere(Criteria::expr()->gte('createdDate', $request->startDate));
        }

        if ($request->endDate) {
            $criteria->andWhere(Criteria::expr()->lte('createdDate', $request->endDate));
        }

        if ($request->limit > 0) {
            $criteria->setMaxResults($request->limit);
        }

        if ($request->offset > 0) {
            $criteria->setFirstResult($request->offset);
        }

        $criteria->orderBy(
            [
                'createdDate' => 'ASC',
                'id' => 'ASC',
            ]
        );

        return $repository->matching($criteria)->toArray();
    }
}
