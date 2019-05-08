<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\AppKey;
use Doctrine\ORM\Query;
use DoctrineExtensions\Query\SortableNullsWalker;

class AppKeyRepository extends BaseRepository
{
    public function getOneByLastUsedDate(): ?AppKey
    {
        $query = $this->createQueryBuilder('ak')
            ->orderBy('ak.lastUsedDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
        $query->setHint(
            'sortableNulls.fields',
            [
                'ak.lastUsedDate' => SortableNullsWalker::NULLS_LAST,
            ]
        );

        return $query->getOneOrNullResult();
    }
}
