<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class QuoteTemplateDataProvider
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
        return $this->entityManager->getRepository(QuoteTemplate::class)
            ->createQueryBuilder('qt')
            ->andWhere('qt.deletedAt IS NULL');
    }

    public function getAllQuoteTemplates(): array
    {
        return $this->entityManager->getRepository(QuoteTemplate::class)
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

    public function isUsedOnOrganization(QuoteTemplate $quoteTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('1')
            ->where('o.quoteTemplate = :quoteTemplate')
            ->setParameter('quoteTemplate', $quoteTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isUsedOnQuote(QuoteTemplate $quoteTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->select('1')
            ->where('q.quoteTemplate = :quoteTemplate')
            ->setParameter('quoteTemplate', $quoteTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
