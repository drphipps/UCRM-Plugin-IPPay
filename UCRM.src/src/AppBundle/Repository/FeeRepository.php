<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\Fee;

class FeeRepository extends BaseRepository
{
    /**
     * @return array|Fee[]
     */
    public function getClientUninvoicedFees(Client $client): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.client = :client')
            ->andWhere('f.invoiced = :invoiced')
            ->setParameter('client', $client)
            ->setParameter('invoiced', false);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getAllInvoiceIds()
    {
        $qb = $this->createQueryBuilder('f')
            ->select('i.id')
            ->join('f.dueInvoice', 'i');

        $invoices = $qb->getQuery()->getArrayResult();

        return array_column($invoices, 'id');
    }
}
