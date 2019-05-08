<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Site;
use AppBundle\Entity\Tariff;
use Doctrine\ORM\NoResultException;

class ServiceRepository extends BaseRepository
{
    public function getActiveServicesWithActiveInPast(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.status IN (:activeStatusesInPast)')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter(
                'activeStatusesInPast',
                array_merge(
                    Service::ACTIVE_STATUSES,
                    [
                        Service::STATUS_DEFERRED,
                        Service::STATUS_ENDED,
                    ]
                )
            );

        return $qb->getQuery()->getResult();
    }

    public function getClientNextInvoicingDay(int $clientId): ?\DateTimeImmutable
    {
        try {
            $minNextInvoicingDay = $this->createQueryBuilder('s')
                ->select('MIN(s.nextInvoicingDay)')
                ->where('s.client = :clientId')
                ->andWhere('s.deletedAt IS NULL')
                ->andWhere('s.status != :quoted')
                ->setParameter('clientId', $clientId)
                ->setParameter('quoted', Service::STATUS_QUOTED)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $exception) {
            $minNextInvoicingDay = null;
        }

        return $minNextInvoicingDay ? new \DateTimeImmutable($minNextInvoicingDay) : null;
    }

    public function getCount(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Service[]|array
     */
    public function getSuspendedServices(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.stopReason', 'ssr')
            ->where('s.status = :status OR (s.activeFrom <= :now AND ssr.id IS NOT NULL AND (s.activeTo IS NULL OR s.activeTo > :now))')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('status', Service::STATUS_SUSPENDED)
            ->setParameter('now', new \DateTime(), UtcDateTimeType::NAME);

        return $qb->getQuery()->getResult();
    }

    public function existsSubscriptionByTariff(Tariff $tariff): bool
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->leftJoin('s.paymentPlan', 'pp')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('s.tariff = :tariff')
            ->andWhere('pp.active = TRUE')
            ->setParameter('tariff', $tariff);

        return (bool) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $ids
     *
     * @return Service[]|null
     */
    public function getByTariffPeriods($ids): ?array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('s.tariffPeriod IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    public function hasPingStatistics(Service $service): bool
    {
        $pingDevices = $service->getServiceDevices()->filter(function (ServiceDevice $serviceDevice) {
            return $serviceDevice->isCreatePingStatistics();
        });

        return $pingDevices->count() > 0;
    }

    public function hasSignalStatistics(Service $service): bool
    {
        $signalDevices = $service->getServiceDevices()->filter(function (ServiceDevice $serviceDevice) {
            return $serviceDevice->getCreateSignalStatistics();
        });

        return $signalDevices->count() > 0;
    }

    public function getServiceDeviceIps(Service $service): array
    {
        $serviceDeviceIps = [];
        foreach ($service->getServiceDevices() as $device) {
            if (null !== $device->getManagementIpAddress()) {
                $serviceDeviceIps[$device->getId()] = long2ip($device->getManagementIpAddress());

                continue;
            }

            if ($serviceIp = $device->getServiceIps()->first()) {
                $serviceDeviceIps[$device->getId()] = long2ip($serviceIp->getIpRange()->getIpAddress());
            }
        }

        ksort($serviceDeviceIps);

        return $serviceDeviceIps;
    }

    /**
     * @param Site[] $sites
     *
     * @return Service[]
     */
    public function getAllForMap(array $sites, ?Client $lead): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s, c, u, sd, i, d, t, cn, st')
            ->join('s.client', 'c')
            ->join('c.user', 'u')
            ->join('s.tariff', 't')
            ->leftJoin('s.serviceDevices', 'sd')
            ->leftJoin('sd.interface', 'i')
            ->leftJoin('i.device', 'd')
            ->leftJoin('c.country', 'cn')
            ->leftJoin('c.state', 'st')
            ->where('s.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL');

        if ($lead) {
            $qb->andWhere('(c.isLead = false OR c.id = :leadId)')
                ->setParameter('leadId', $lead->getId());
        } else {
            $qb->andWhere('c.isLead = false');
        }

        if ($sites) {
            $qb->andWhere('d.site IN (:sites)')
                ->setParameter('sites', $sites);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Service[]|array
     */
    public function getSeparatelyInvoicedServicesForInvoicing(Client $client, \DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('s')
            ->addSelect('c')
            ->join('s.client', 'c')
            ->andWhere('s.client = :client')
            ->andWhere('s.deletedAt IS NULL OR s.status = :obsolete')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('s.nextInvoicingDay <= :date')
            ->andWhere('s.activeTo IS NULL OR s.invoicingLastPeriodEnd IS NULL OR s.invoicingLastPeriodEnd <= s.activeTo')
            ->andWhere('s.invoicingSeparately = TRUE')
            ->andWhere('s.status != :quoted')
            ->orderBy('s.id')
            ->setParameter(':client', $client)
            ->setParameter(':date', $date->format('Y-m-d'))
            ->setParameter(':obsolete', Service::STATUS_OBSOLETE)
            ->setParameter(':quoted', Service::STATUS_QUOTED);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Service[]|array
     */
    public function getNonSeparatelyInvoicedServicesForInvoicing(\DateTimeImmutable $date, Client $client): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.client', 'c')
            ->andWhere('s.deletedAt IS NULL OR s.status = :obsolete')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('s.nextInvoicingDay <= :date')
            ->andWhere('s.activeTo IS NULL OR s.invoicingLastPeriodEnd IS NULL OR s.invoicingLastPeriodEnd <= s.activeTo')
            ->andWhere('s.invoicingSeparately = FALSE')
            ->andWhere('c = :client')
            ->andWhere('s.status != :quoted')
            ->orderBy('s.id')
            ->setParameter(':date', $date->format('Y-m-d'))
            ->setParameter(':obsolete', Service::STATUS_OBSOLETE)
            ->setParameter(':client', $client)
            ->setParameter(':quoted', Service::STATUS_QUOTED);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Service[]|array
     */
    public function getServicesWithDeferredChanges(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->innerJoin('s.supersededByService', 'd')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('d.status = :deferred')
            ->andWhere('d.invoicingStart <= :date')
            ->setParameter('deferred', Service::STATUS_DEFERRED)
            ->setParameter('date', $date->format('Y-m-d'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int[] $organizationIds
     *
     * @return Service[]
     */
    public function getServicesForFccReport(array $organizationIds): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('c')
            ->addSelect('o')
            ->addSelect('t')
            ->addSelect('u')
            ->join('s.client', 'c')
            ->join('c.user', 'u')
            ->join('c.organization', 'o')
            ->join('s.tariff', 't')
            ->andWhere('c.organization IN (:organizationIds)')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('s.status IN (:activeStatuses)')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->andWhere('t.includedInFccReports = TRUE')
            ->addOrderBy('s.id')
            ->setParameter('organizationIds', $organizationIds)
            ->setParameter('activeStatuses', Service::ACTIVE_STATUSES)
            ->indexBy('s', 's.id')
            ->getQuery()
            ->getResult();
    }

    public function getNotDeletedServiceByIp(int $ipInLong): ?Service
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->join('s.serviceDevices', 'sd')
            ->join('sd.serviceIps', 'sip')
            ->where('sip.ipRange.firstIp <= :ipInLong')
            ->andWhere('sip.ipRange.lastIp >= :ipInLong')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('ipInLong', $ipInLong)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function existsAnyNotDeleted(): bool
    {
        return (bool) $this->createQueryBuilder('s')
            ->select('1')
            ->join('s.client', 'c')
            ->where('s.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isLead = false')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getSendEmailsAutomaticallyAveragePercentage(bool $systemDefault): float
    {
        $average = (float) $this->createQueryBuilder('s')
            ->select('AVG(cast_as_integer(COALESCE(s.sendEmailsAutomatically, :systemDefault)))')
            ->join('s.client', 'c')
            ->where('s.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('systemDefault', $systemDefault)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return round($average * 100, 2);
    }

    public function getPeriodTypeBackwardAveragePercentage(): float
    {
        $average = (float) $this->createQueryBuilder('s')
            ->select('AVG(case when s.invoicingPeriodType = :backward then 1 else 0 end)')
            ->join('s.client', 'c')
            ->where('s.deletedAt IS NULL')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('backward', Service::INVOICING_BACKWARDS)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return round($average * 100, 2);
    }

    /**
     * @param int[] $ids
     *
     * @return Service[]
     */
    public function getServicesWithPaymentPlans(array $ids): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('pp')
            ->leftJoin('s.paymentPlans', 'pp')
            ->andWhere('s.deletedAt IS NULL')
            ->getQuery()->getResult();
    }
}
