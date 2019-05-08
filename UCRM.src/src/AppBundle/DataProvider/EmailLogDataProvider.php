<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Mailing;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class EmailLogDataProvider
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getQueryBuilder(
        Invoice $invoice = null,
        Client $client = null,
        Mailing $mailing = null,
        Quote $quote = null
    ): QueryBuilder {
        $qb = $this->entityManager->getRepository(EmailLog::class)
            ->createQueryBuilder('l')
            ->addOrderBy('l.createdDate', 'DESC')
            ->addOrderBy('l.id', 'DESC');

        if ($invoice) {
            $qb->andWhere('l.invoice = :invoiceId')
                ->setParameter(':invoiceId', $invoice->getId());
        }

        if ($client) {
            $qb->andWhere('l.client = :clientId')
                ->setParameter(':clientId', $client->getId());
        }

        if ($mailing) {
            $qb->andWhere('l.bulkMail = :MailingId')
                ->setParameter(':MailingId', $mailing->getId());
        }

        if ($quote) {
            $qb->andWhere('l.quote = :quoteId')
                ->setParameter(':quoteId', $quote->getId());
        }

        return $qb;
    }

    public function getFailedNotDiscardedGridModel(): QueryBuilder
    {
        return $this->getQueryBuilder()
            ->addSelect('l.id AS el_id')
            ->where('l.discarded != true')
            ->andWhere('l.status = :status')
            ->setParameter('status', EmailLog::STATUS_ERROR)
            ->andWhere('l.createdDate >= :createdDate')
            ->setParameter('createdDate', (new \DateTime())->modify('-1 month midnight'), UtcDateTimeType::NAME);
    }

    /**
     * @return int[]
     */
    public function getFailedEmailIdsSince(\DateTimeInterface $date): array
    {
        $result = $this->getQueryBuilder()
            ->select('l.id')
            ->andWhere('l.createdDate >= (:resendSince)')
            ->andWhere('trim(l.recipient) != \'\'')
            ->setParameter('resendSince', $date, UtcDateTimeType::NAME)
            ->andWhere('l.status = (:status)')
            ->setParameter('status', EmailLog::STATUS_ERROR)
            ->getQuery()->getResult();

        return array_column($result, 'id');
    }

    public function getFailedEmailsById(array $logIds): array
    {
        return $this->getQueryBuilder()
            ->andWhere('l.id IN (:ids)')
            ->setParameter('ids', $logIds)
            ->andWhere('l.status = (:status)')
            ->setParameter('status', EmailLog::STATUS_ERROR)
            ->getQuery()->getResult();
    }
}
