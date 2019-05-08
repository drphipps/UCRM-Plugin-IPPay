<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class OrganizationDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getNames(): array
    {
        $organizations = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->getQuery()
            ->getResult();

        $organizationNames = [];
        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $organizationNames[$organization->getId()] = $organization->getName();
        }

        return $organizationNames;
    }

    public function getEmails(): array
    {
        $organizations = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->getQuery()
            ->getResult();

        $organizationEmails = [];
        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $organizationEmails[$organization->getId()] = $organization->getEmail();
        }

        return $organizationEmails;
    }

    /**
     * @return Currency[]
     */
    public function getUsedCurrencies(): array
    {
        return $this->entityManager->getRepository(Currency::class)
            ->createQueryBuilder('c')
            ->select('c')
            ->innerJoin(Organization::class, 'o', Join::WITH, 'c.id = o.currency')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function hasClients(Organization $organization): bool
    {
        return (bool) $this->entityManager->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->select('1')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
