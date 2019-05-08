<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Repository;

use ApiBundle\Entity\UserAuthenticationKey;
use AppBundle\Repository\BaseRepository;
use Doctrine\ORM\Query;
use DoctrineExtensions\Query\SortableNullsWalker;

class UserAuthenticationKeyRepository extends BaseRepository
{
    public function getOneByLastUsedDate(): ?UserAuthenticationKey
    {
        $query = $this->createQueryBuilder('uak')
            ->orderBy('uak.lastUsedDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
        $query->setHint(
            'sortableNulls.fields',
            [
                'uak.lastUsedDate' => SortableNullsWalker::NULLS_LAST,
            ]
        );

        return $query->getOneOrNullResult();
    }
}
