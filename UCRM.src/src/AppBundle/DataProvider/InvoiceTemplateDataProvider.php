<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class InvoiceTemplateDataProvider
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
        return $this->entityManager->getRepository(InvoiceTemplate::class)
            ->createQueryBuilder('it')
            ->andWhere('it.deletedAt IS NULL');
    }

    public function getAllInvoiceTemplates(): array
    {
        return $this->entityManager->getRepository(InvoiceTemplate::class)
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

    public function isUsedOnOrganization(InvoiceTemplate $invoiceTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('1')
            ->where('o.invoiceTemplate = :invoiceTemplate')
            ->setParameter('invoiceTemplate', $invoiceTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isUsedOnInvoice(InvoiceTemplate $invoiceTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('1')
            ->where('i.invoiceTemplate = :invoiceTemplate')
            ->setParameter('invoiceTemplate', $invoiceTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
