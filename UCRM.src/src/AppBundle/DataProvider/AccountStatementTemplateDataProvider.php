<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AccountStatementTemplateDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(AccountStatementTemplate::class)
            ->createQueryBuilder('ast')
            ->andWhere('ast.deletedAt IS NULL');
    }

    public function getAllAccountStatementTemplates(): array
    {
        return $this->entityManager->getRepository(AccountStatementTemplate::class)
            ->findBy(
                [
                    'deletedAt' => null,
                ],
                [
                    'name' => 'ASC',
                    'id' => 'ASC',
                ]
            );
    }

    public function isUsedOnOrganization(AccountStatementTemplate $accountStatementTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('1')
            ->where('o.accountStatementTemplate = :accountStatementTemplate')
            ->setParameter('accountStatementTemplate', $accountStatementTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
