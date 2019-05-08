<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class ClientZonePageRepository extends BaseRepository
{
    public function getMaxPosition(): int
    {
        try {
            return (int) $this->createQueryBuilder('clientZonePage')
                ->select('MAX(clientZonePage.position)')
                ->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException | NoResultException $exception) {
            return 0;
        }
    }
}
