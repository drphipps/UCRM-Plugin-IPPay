<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Repository;

use AppBundle\Repository\BaseRepository;

class TicketImapInboxRepository extends BaseRepository
{
    public function exists(): bool
    {
        return (bool) $this->createQueryBuilder('i')
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
