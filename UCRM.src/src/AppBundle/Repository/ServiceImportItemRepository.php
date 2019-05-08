<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Import\ClientImport;

class ServiceImportItemRepository extends BaseRepository
{
    public function getCount(ClientImport $import)
    {
        return (int) $this->createQueryBuilder('sii')
            ->select('COUNT(sii.id)')
            ->leftJoin('sii.importItem', 'cii')
            ->andWhere('cii.import = :import')
            ->setParameter('import', $import)
            ->getQuery()->getSingleScalarResult();
    }
}
