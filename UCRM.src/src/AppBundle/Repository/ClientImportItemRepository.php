<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Import\ClientImport;
use AppBundle\Entity\Import\ClientImportItem;

class ClientImportItemRepository extends BaseRepository
{
    /**
     * @return ClientImportItem[]
     */
    public function getItems(ClientImport $import, int $limit): array
    {
        return $this->createQueryBuilder('cii')
            ->select('cii, ciiv, ciis, ciisv')
            ->leftJoin('cii.validationErrors', 'ciiv')
            ->leftJoin('cii.serviceItems', 'ciis')
            ->leftJoin('ciis.validationErrors', 'ciisv')
            ->andWhere('cii.import = :import')
            ->orderBy('cii.hasErrors', 'DESC')
            ->addOrderBy('cii.lineNumber', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('import', $import)
            ->getQuery()->getResult();
    }

    /**
     * @return ClientImportItem[]
     */
    public function getItemsForSaving(ClientImport $import): array
    {
        return $this->createQueryBuilder('cii')
            ->select('cii, ciiv, ciis, ciisv')
            ->leftJoin('cii.validationErrors', 'ciiv')
            ->leftJoin('cii.serviceItems', 'ciis')
            ->leftJoin('ciis.validationErrors', 'ciisv')
            ->andWhere('cii.import = :import')
            ->andWhere('cii.canImport = true')
            ->andWhere('cii.doImport = true')
            ->orderBy('cii.lineNumber', 'ASC')
            ->setParameter('import', $import)
            ->getQuery()->getResult();
    }

    public function getCountForImportStart(ClientImport $import): int
    {
        return (int) $this->createQueryBuilder('cii')
            ->select('COUNT(cii.id)')
            ->andWhere('cii.import = :import')
            ->andWhere('cii.canImport = true')
            ->andWhere('cii.doImport = true')
            ->setParameter('import', $import)
            ->getQuery()->getSingleScalarResult();
    }

    public function getCountWithoutServiceItems(ClientImport $import): int
    {
        return (int) $this->createQueryBuilder('cii')
            ->select('COUNT(cii.id)')
            ->leftJoin('cii.serviceItems', 'ciis')
            ->andWhere('cii.import = :import')
            ->andWhere('ciis.id IS NULL')
            ->setParameter('import', $import)
            ->getQuery()->getSingleScalarResult();
    }

    public function isUserIdentUniqueWithinImport(ClientImportItem $item): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.userIdent = :userIdent')
            ->andWhere('c.import = :importId')
            ->andWhere('c.id != :id')
            ->setParameter('userIdent', $item->getUserIdent())
            ->setParameter('importId', $item->getImport()->getId())
            ->setParameter('id', $item->getId());

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    public function isUsernameUniqueWithinImport(ClientImportItem $item): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.username = :username')
            ->andWhere('c.import = :importId')
            ->andWhere('c.id != :id')
            ->setParameter('username', $item->getUsername())
            ->setParameter('importId', $item->getImport()->getId())
            ->setParameter('id', $item->getId());

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }
}
