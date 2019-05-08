<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ProformaInvoiceTemplateDataProvider
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
        return $this->entityManager->getRepository(ProformaInvoiceTemplate::class)
            ->createQueryBuilder('it')
            ->andWhere('it.deletedAt IS NULL');
    }

    public function findAllTemplates(): array
    {
        return $this->entityManager->getRepository(ProformaInvoiceTemplate::class)
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

    public function isUsedOnOrganization(ProformaInvoiceTemplate $proformaInvoiceTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('1')
            ->where('o.proformaInvoiceTemplate = :proformaInvoiceTemplate')
            ->setParameter('proformaInvoiceTemplate', $proformaInvoiceTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isUsedOnInvoice(ProformaInvoiceTemplate $proformaInvoiceTemplate): bool
    {
        return (bool) $this->entityManager->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('1')
            ->where('i.proformaInvoiceTemplate = :proformaInvoiceTemplate')
            ->setParameter('proformaInvoiceTemplate', $proformaInvoiceTemplate)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
