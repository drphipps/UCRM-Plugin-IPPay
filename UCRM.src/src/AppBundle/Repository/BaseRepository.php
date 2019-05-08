<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class BaseRepository extends EntityRepository
{
    /**
     * @deprecated
     */
    protected function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }

    /**
     * @deprecated
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * Loads all related entities of one type with one query. Better than regular join because it optimizes hydration.
     *
     * @see http://ocramius.github.io/blog/doctrine-orm-optimization-hydration/
     *
     * @param string|array $properties
     */
    public function loadRelatedEntities($properties, array $values, string $field = 'id')
    {
        if (! $values) {
            return;
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->from($this->_entityName, 'e0');

        $i = 0;
        foreach ((array) $properties as $property) {
            $qb->addSelect(sprintf('PARTIAL e%d.{id}', $i));
            $qb->leftJoin(sprintf('e%d.%s', $i, $property), sprintf('e%d', ++$i));
        }

        $qb->addSelect(sprintf('e%d', $i));
        $qb->andWhere(sprintf('e0.%s IN (:values)', $field));
        $qb->setParameter('values', $values);

        $qb->getQuery()->getResult();
    }

    public function getApproximateCount(): int
    {
        $stmt = $this->_em->getConnection()->executeQuery(
            '
                SELECT reltuples::BIGINT
                FROM pg_class
                WHERE relname = :tableName
            ',
            [
                ':tableName' => $this->_class->getTableName(),
            ]
        );

        return (int) $stmt->fetchColumn();
    }
}
