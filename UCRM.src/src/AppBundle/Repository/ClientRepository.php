<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccountingView;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class ClientRepository extends BaseRepository
{
    public const SITE_DEVICE_SEPARATOR = '%separator%';
    public const SITE_DEVICE_DELIMITER = '%delimiter%';

    /**
     * @return Client[]|array
     */
    public function getClientsForInvoicing(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->join('c.services', 's')
            ->andWhere('s.deletedAt IS NULL OR s.status = :obsolete')
            ->andWhere('s.nextInvoicingDay <= :date')
            ->andWhere(
                's.activeTo IS NULL OR s.invoicingLastPeriodEnd IS NULL OR s.invoicingLastPeriodEnd <= s.activeTo'
            )
            ->andWhere('s.invoicingSeparately = FALSE')
            ->andWhere('s.status != :quoted')
            ->setParameter(':date', $date->format('Y-m-d'))
            ->setParameter(':obsolete', Service::STATUS_OBSOLETE)
            ->setParameter(':quoted', Service::STATUS_QUOTED);

        return $qb->getQuery()->getResult();
    }

    public function getAllQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('c, u, cc.code AS currencyCode, c.balance AS c_balance, o.name AS o_name')
            ->addSelect('string_agg_distinct(t.name, \', \') AS tariffs')
            ->addSelect(
                sprintf(
                    'string_agg_multi_distinct(site.name, d.name, \'%s\', \'%s\') AS devices',
                    self::SITE_DEVICE_SEPARATOR,
                    self::SITE_DEVICE_DELIMITER
                )
            )
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->addSelect('c.hasOverdueInvoice AS c_has_overdue_invoice')
            ->addSelect('c.hasSuspendedService AS c_has_suspended_service')
            ->addSelect('c.hasOutage AS c_has_outage')
            ->join('c.user', 'u')
            ->join('c.organization', 'o')
            ->leftJoin('o.currency', 'cc')
            ->leftJoin('c.services', 's', Join::WITH, 's.deletedAt IS NULL')
            ->leftJoin('s.tariff', 't')
            ->leftJoin('s.serviceDevices', 'ssd')
            ->leftJoin('ssd.interface', 'ssdi')
            ->leftJoin('ssdi.device', 'd')
            ->leftJoin('d.site', 'site')
            ->leftJoin('c.clientTags', 'ct')
            ->groupBy('c.id, u.id, cc.code, o.id');

        return $queryBuilder;
    }

    public function getNextClientCustomId(?Organization $organization = null): int
    {
        // return 1 if this is first client ever
        $maxId = (int) $this->createQueryBuilder('c')->select('MAX(c.id)')->getQuery()->getSingleScalarResult();
        if (! $maxId) {
            return 1;
        }

        // if used custom IDs contain anything but numbers, we do not support incrementing them
        if ($this->getCountOfCustomIdsNotCastableToInteger($organization) > 0) {
            $maxCustomId = $maxId;
        } elseif ($organization) {
            $maxCustomId = (int) $this->_em->getConnection()->fetchColumn(
                'SELECT user_ident_int FROM client WHERE organization_id = :id ORDER BY user_ident_int DESC LIMIT 1',
                [
                    'id' => $organization->getId(),
                ]
            );
        } else {
            $maxCustomId = (int) $this->_em->getConnection()->fetchColumn(
                'SELECT user_ident_int FROM client ORDER BY user_ident_int DESC LIMIT 1'
            );
        }

        // if we don't yet have any custom ID, use regular ID
        $nextCustomId = $maxCustomId ? $maxCustomId + 1 : $maxId + 1;

        // if the incremented custom ID already exists anywhere, increase it
        // it has to be unique and could exist in different organization
        $result = $this->createQueryBuilder('c')
            ->select('c.userIdent')
            ->getQuery()
            ->getScalarResult();
        $existingCustomIds = array_filter(array_column($result, 'userIdent'));

        while (in_array($nextCustomId, $existingCustomIds, true)) {
            ++$nextCustomId;
        }

        return $nextCustomId;
    }

    private function getCountOfCustomIdsNotCastableToInteger(?Organization $organization): int
    {
        if ($organization) {
            return (int) $this->_em->getConnection()->fetchColumn(
                '
                  SELECT
                    COUNT(*)
                  FROM
                    client
                  WHERE
                    COALESCE(user_ident_int::text, \'\') != COALESCE(user_ident, \'\')
                    AND organization_id = organization_id
                ',
                [
                    'organizationId' => $organization->getId(),
                ]
            );
        }

        return (int) $this->_em->getConnection()->fetchColumn(
            '
              SELECT
                COUNT(*)
              FROM
                client
              WHERE
                COALESCE(user_ident_int::text, \'\') != COALESCE(user_ident, \'\')
            '
        );
    }

    public function getMaxClientId(): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('MAX(c.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getCountClientsWithoutInvitationEmail(): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.user', 'u')
            ->where('c.invitationEmailSentDate IS NULL')
            ->andWhere('u.isActive= :isActive')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->setParameter('isActive', false);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array|Client[]
     */
    public function getClientsWithoutInvitationEmail(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.invitationEmailSentDate IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('c.deletedAt IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int|array $clientIds
     *
     * @deprecated use AppBundle\Subscriber\Client\UpdateClientAccountStandingsSubscriber instead
     */
    public function countAccountStandings($clientIds): void
    {
        $clientIds = is_array($clientIds)
            ? array_filter(array_unique($clientIds))
            : [$clientIds];

        foreach ($clientIds as $clientId) {
            /** @var Client $client */
            $client = $this->find($clientId);

            $this->getEntityManager()->refresh($client);

            $accountStandingsCredit = 0.0;
            $accountStandingsRefundableCredit = 0.0;
            foreach ($client->getCredits() as $credit) {
                if ($credit->getPayment()->getClient() !== $client) {
                    continue;
                }

                $accountStandingsCredit += $credit->getAmount();

                if ($credit->getPayment()->getMethod() !== Payment::METHOD_COURTESY_CREDIT) {
                    $accountStandingsRefundableCredit += $credit->getAmount();
                }
            }

            $accountStandingsOutstanding = 0.0;
            foreach ($client->getInvoices() as $invoice) {
                if (! in_array($invoice->getInvoiceStatus(), Invoice::VALID_STATUSES, true)) {
                    continue;
                }

                $accountStandingsOutstanding += $invoice->getAmountToPay();
            }

            $client->setAccountStandingsCredit($accountStandingsCredit);
            $client->setAccountStandingsRefundableCredit($accountStandingsRefundableCredit);
            $client->setAccountStandingsOutstanding($accountStandingsOutstanding);
            $client->setBalance($accountStandingsCredit - $accountStandingsOutstanding);
        }

        $this->getEntityManager()->flush();
    }

    public function isUserIdentUnique(string $userIdent): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.userIdent = :userIdent')
            ->setParameter('userIdent', $userIdent);

        return ((int) $qb->getQuery()->getSingleScalarResult()) === 0;
    }

    public function createElasticQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c');
    }

    public function getTrafficQueryBuilder(\DateTimeImmutable $from, \DateTimeImmutable $to): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->addSelect('client_full_name(c, u) AS c_fullname')
            ->join('c.user', 'u')
            ->join('c.services', 's')
            ->join(ServiceAccountingView::class, 'a', Join::WITH, 'a.service = s')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('a.date >= :from')
            ->andWhere('a.date <= :to')
            ->groupBy('c.id, u.firstName, u.lastName')
            ->setParameter('from', $from, UtcDateTimeType::NAME)
            ->setParameter('to', $to, UtcDateTimeType::NAME);
    }

    public function getMailingPreviewData(
        ?array $organizations,
        ?array $clientTypes,
        ?array $clientTags,
        ?array $servicePlans,
        ?array $periodStartDays,
        ?array $sites,
        ?array $devices,
        ?bool $includeLeads,
        ?array $clientId
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->addSelect('client_full_name(c, u) AS HIDDEN full_name')
            ->addSelect('u')
            ->innerJoin('c.user', 'u')
            ->leftJoin('c.services', 'sr', Join::WITH, 'sr.deletedAt IS NULL')
            ->leftJoin('sr.serviceDevices', 'sd')
            ->leftJoin('sd.interface', 'i')
            ->leftJoin('i.device', 'd')
            ->leftJoin('d.site', 's')
            ->andWhere('c.deletedAt IS NULL')
            ->addOrderBy('full_name');

        if (null !== $includeLeads) {
            $qb->andWhere('c.isLead = :includeLeads')
                ->setParameter('includeLeads', $includeLeads);
        }

        if ($organizations) {
            $qb->andWhere('c.organization IN (:organizations)')
                ->setParameter('organizations', $organizations);
        }

        if ($clientTags) {
            $qb->leftJoin('c.clientTags', 'ct')
                ->andWhere('ct.id IN (:clientTags)')
                ->setParameter('clientTags', $clientTags);
        }

        if ($servicePlans) {
            $qb->leftJoin('sr.tariff', 't')
                ->andWhere('sr.tariff IN (:tariffs)')
                ->andWhere('sr.status IN (:status)')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('tariffs', $servicePlans)
                ->setParameter('status', Service::ACTIVE_STATUSES);
        }

        if ($sites) {
            $qb->andWhere('d.site IN (:sites)')
                ->andWhere('i.deletedAt IS NULL')
                ->andWhere('d.deletedAt IS NULL')
                ->andWhere('s.deletedAt IS NULL')
                ->setParameter('sites', $sites);
        }

        if ($devices) {
            $qb->andWhere('i.device IN (:devices)')
                ->andWhere('i.deletedAt IS NULL')
                ->andWhere('d.deletedAt IS NULL')
                ->setParameter('devices', $devices);
        }

        if ($clientTypes) {
            $qb->andWhere('c.clientType IN (:clientTypes)')
                ->setParameter('clientTypes', $clientTypes);
        }

        if ($periodStartDays) {
            $qb->andWhere('sr.invoicingPeriodStartDay IN (:periodStartDays)')
                ->setParameter('periodStartDays', $periodStartDays);
        }

        if ($clientId) {
            // result WHERE will be '( xx AND xx AND xx ... ) OR c IN(:clientId)'
            $qb->orWhere('c IN (:clientId)')
                ->setParameter('clientId', $clientId);
        }

        $clients = $qb->getQuery()->getResult();
        $ids = array_map(
            function (Client $client) {
                return $client->getId();
            },
            $clients
        );

        $this->loadRelatedEntities('organization', $ids);

        return $clients;
    }

    public function getClients(array $clientIds): array
    {
        return $this->findBy(
            [
                'id' => $clientIds,
                'deletedAt' => null,
            ]
        );
    }

    public function getStripeAchVerifiedAccounts(int $clientId): array
    {
        return $this->createQueryBuilder('c')
            ->select('cba.id, cba.accountNumber, cba.stripeBankAccountId')
            ->leftJoin('c.bankAccounts', 'cba')
            ->where('c.id = :clientId')
            ->andWhere('cba.stripeBankAccountVerified = TRUE')
            ->setParameter('clientId', $clientId)
            ->getQuery()->getResult();
    }

    public function existsAnyNotDeleted(): bool
    {
        return (bool) $this->createQueryBuilder('c')
            ->select('1')
            ->where('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * If there is only one client in database, use him for onboarding box on dashboard.
     */
    public function getClientForNewServiceOnboarding(): ?Client
    {
        $clients = $this->findBy(
            [
                'deletedAt' => null,
            ],
            [],
            2
        );

        return count($clients) === 1 ? reset($clients) : null;
    }

    public function getCountQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c)');
    }

    public function getCount(): int
    {
        return (int) $this->getCountQueryBuilder()->getQuery()->getSingleScalarResult();
    }

    public function getGenerateProformaInvoicesAveragePercentage(bool $systemDefault): float
    {
        $average = (float) $this->createQueryBuilder('c')
            ->select('AVG(cast_as_integer(COALESCE(c.generateProformaInvoices, :systemDefault)))')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('systemDefault', $systemDefault)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return round($average * 100, 2);
    }
}
