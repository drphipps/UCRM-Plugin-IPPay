<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Tax;

use AppBundle\Entity\Tax;
use Doctrine\ORM\EntityManager;

class TaxReplacer
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function replaceTax(string $entityClass, string $column, Tax $tax, Tax $supersededTax): void
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->update($entityClass, 'e')
            ->set(sprintf('e.%s', $column), ':new')
            ->where(sprintf('e.%s = :old', $column))
            ->setParameters(
                [
                    'new' => $tax->getId(),
                    'old' => $supersededTax->getId(),
                ]
            );

        $qb->getQuery()->execute();
    }
}
