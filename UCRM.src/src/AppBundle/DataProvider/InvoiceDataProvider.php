<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Request\InvoiceCollectionRequest;
use AppBundle\Component\Export\ExportPathData;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\InvoiceAttribute;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentToken;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Service\Options;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class InvoiceDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    /**
     * @var Options
     */
    private $options;

    public function __construct(EntityManagerInterface $entityManager, PdfHandler $pdfHandler, Options $options)
    {
        $this->entityManager = $entityManager;
        $this->pdfHandler = $pdfHandler;
        $this->options = $options;
    }

    /**
     * @return Invoice[]
     */
    public function getInvoiceCollection(InvoiceCollectionRequest $request): array
    {
        $qb = $this->entityManager->getRepository(Invoice::class)->getQueryBuilder($request);

        $criteria = new Criteria();
        $orderBy = [
            $request->order ?: 'createdDate' => $request->direction ?: 'ASC',
        ];

        if (in_array($request->order, ['clientFirstName', 'clientLastName'], true)) {
            array_merge(
                $orderBy,
                [
                    'clientCompanyName' => $request->direction,
                ]
            );
        }
        $criteria->orderBy(
            array_merge(
                $orderBy,
                [
                    'id' => $request->direction ?: 'ASC',
                ]
            )
        );
        $qb->addCriteria($criteria);

        return $qb->getQuery()->getResult();
    }

    public function getGridModel(Client $client = null): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Invoice::class)->createQueryBuilder('i');
        $queryBuilder
            ->addSelect('i.invoiceNumber as i_invoice_number, i.invoiceStatus as i_invoice_status')
            ->addSelect('cc, o.name as o_name')
            ->addSelect('i.total as i_total, GREATEST(i.total - i.amountPaid, 0) as i_to_pay')
            ->addSelect(
                'CASE WHEN i.invoiceStatus IN (:unpaid) AND i.dueDate < :today THEN true ELSE false END AS overdue'
            )
            ->addSelect('ipt')
            ->join('i.currency', 'cc')
            ->join('i.organization', 'o')
            ->leftJoin('i.invoiceItems', 'ii')
            ->leftJoin('i.paymentToken', 'ipt')
            ->orderBy('i.id')
            ->groupBy('i.id, cc.id, o.id, ipt.id')
            ->setParameter('unpaid', Invoice::UNPAID_STATUSES)
            ->setParameter('today', new \DateTime('today midnight'), UtcDateTimeType::NAME);

        if ($client) {
            $queryBuilder
                ->andWhere('i.client = :clientId')
                ->setParameter('clientId', $client->getId());
        } else {
            $queryBuilder
                ->addSelect('client_full_name(c, u) AS c_fullname')
                ->join('i.client', 'c')
                ->join('c.user', 'u')
                ->addGroupBy('c.id, u.id');
        }

        return $queryBuilder;
    }

    public function getGridPostFetchCallback(): \Closure
    {
        return function ($result) {
            $ids = array_map(
                function (array $row) {
                    /** @var Invoice $invoice */
                    $invoice = $row[0];

                    return $invoice->getClient()->getId();
                },
                $result
            );
            $this->entityManager->getRepository(Client::class)->loadRelatedEntities('contacts', $ids);

            $contactIds = [];
            foreach ($result as $row) {
                /** @var Invoice $invoice */
                $invoice = $row[0];

                foreach ($invoice->getClient()->getContacts() as $contact) {
                    $contactIds[] = $contact->getId();
                }
            }
            $this->entityManager->getRepository(ClientContact::class)->loadRelatedEntities('types', $contactIds);
        };
    }

    public function getInvoiceIdsWithPendingPayments($client): array
    {
        return $this->entityManager->getRepository(PaymentToken::class)
            ->createQueryBuilder('pt')
            ->select('i.id')
            ->join('pt.paymentStripePending', 'pst')
            ->join('pt.invoice', 'i')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @return ExportPathData[]
     */
    public function getAllInvoicePdfPathsForClient(Client $client): array
    {
        $request = new InvoiceCollectionRequest();
        $request->clientId = $client->getId();
        $invoices = $this->getInvoiceCollection($request);

        $paths = [];
        foreach ($invoices as $invoice) {
            $path = $this->pdfHandler->getFullInvoicePdfPath($invoice);
            if (! $path) {
                continue;
            }

            $paths[] = new ExportPathData(sprintf('%s.pdf', $invoice->getInvoiceNumber()), $path);
        }

        return $paths;
    }

    public function showProformaInvoices(): bool
    {
        return $this->options->get(Option::GENERATE_PROFORMA_INVOICES)
            || (bool) $this->entityManager->getRepository(Invoice::class)
                ->createQueryBuilder('i')
                ->select('1')
                ->where('i.isProforma = :isProforma')
                ->setParameter('isProforma', true)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function hasInvoiceAttributes(Invoice $invoice): bool
    {
        return (bool) $this->entityManager->getRepository(InvoiceAttribute::class)
            ->createQueryBuilder('ia')
            ->select('1')
            ->andWhere('ia.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
