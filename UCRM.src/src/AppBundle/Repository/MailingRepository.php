<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;

class MailingRepository extends BaseRepository
{
    public function getMailingQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('m');
    }

    public function existsAny(): bool
    {
        return (bool) $this->createQueryBuilder('m')
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
