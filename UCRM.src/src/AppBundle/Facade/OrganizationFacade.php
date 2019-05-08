<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;
use AppBundle\FileManager\OrganizationFileManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class OrganizationFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var OrganizationFileManager
     */
    private $organizationFileManager;

    public function __construct(EntityManager $em, OrganizationFileManager $organizationFileManager)
    {
        $this->em = $em;
        $this->organizationFileManager = $organizationFileManager;
    }

    public function getAllOrganizations(): array
    {
        $repository = $this->em->getRepository(Organization::class);

        return $repository->findBy([], ['id' => 'ASC']);
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->addSelect('cur.code AS c_currency')
            ->addSelect('it.id AS o_invoiceTemplate')
            ->addSelect('qt.id AS o_quoteTemplate')
            ->addSelect('prt.id AS o_receiptTemplate')
            ->leftJoin('o.clients', 'c')
            ->leftJoin('o.currency', 'cur')
            ->leftJoin('o.invoiceTemplate', 'it')
            ->leftJoin('o.quoteTemplate', 'qt')
            ->leftJoin('o.paymentReceiptTemplate', 'prt')
            ->groupBy('o.id, cur.code, it.id, qt.id, prt.id');
    }

    public function isPaymentProviderConfigured(Organization $organization, bool $sandbox): bool
    {
        $paypal = $organization->getPayPalClientId($sandbox)
            && $organization->getPayPalClientSecret($sandbox);

        $stripe = $organization->getStripePublishableKey($sandbox)
            && $organization->getStripeSecretKey($sandbox);

        $anet = $organization->getAnetTransactionKey($sandbox)
            && $organization->getAnetLoginId($sandbox);

        return $paypal || $stripe || $anet;
    }

    public function findAllForm(): array
    {
        return $this->em->getRepository(Organization::class)->findAllForm();
    }

    public function handleNew(Organization $organization): void
    {
        $this->handleDefaults($organization);
        $this->handleUploads($organization);
        $this->em->persist($organization);
        $this->em->flush();
    }

    public function handleEdit(Organization $organization): void
    {
        $this->handleDefaults($organization);
        $this->handleUploads($organization);
        $this->em->flush();
    }

    public function handleRemoveLogo(Organization $organization): void
    {
        if (! $organization->getLogo()) {
            return;
        }

        $this->organizationFileManager->deleteLogo($organization->getLogo());
        $organization->setLogo(null);
        $this->em->flush();
    }

    public function handleRemoveStamp(Organization $organization): void
    {
        if (! $organization->getStamp()) {
            return;
        }

        $this->organizationFileManager->deleteStamp($organization->getStamp());
        $organization->setStamp(null);
        $this->em->flush();
    }

    public function handleDelete(Organization $organization): void
    {
        $this->em->transactional(
            function () use ($organization) {
                foreach ($organization->getTariffs() as $tariff) {
                    if ($tariff->getServices()->isEmpty()) {
                        $this->em->remove($tariff);
                    }
                }

                $this->em->remove($organization);
            }
        );
    }

    public function hasRelationToFinancialEntities(Organization $organization): bool
    {
        $invoices = $this->em->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('1')
            ->join('i.client', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $quotes = $this->em->getRepository(Quote::class)
            ->createQueryBuilder('q')
            ->select('1')
            ->join('q.client', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $payments = $this->em->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('1')
            ->join('p.client', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $refunds = $this->em->getRepository(Refund::class)
            ->createQueryBuilder('r')
            ->select('1')
            ->join('r.client', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return ! (! $invoices && ! $quotes && ! $payments && ! $refunds);
    }

    private function handleUploads(Organization $organization): void
    {
        if ($organization->getFileLogo()) {
            $organization->setLogo(
                $this->organizationFileManager->uploadLogo($organization->getFileLogo())
            );
        }

        if ($organization->getFileStamp()) {
            $organization->setStamp(
                $this->organizationFileManager->uploadStamp($organization->getFileStamp())
            );
        }
    }

    private function handleDefaults(Organization $organization): void
    {
        if (! $organization->isRoundingTotalEnabled()) {
            $organization->setInvoicedTotalRoundingPrecision(null);
        }
    }
}
