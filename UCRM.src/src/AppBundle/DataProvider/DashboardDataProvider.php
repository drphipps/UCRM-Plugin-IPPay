<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\Service\TimePeriod;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\ServiceController;
use AppBundle\Controller\SettingApplicationController;
use AppBundle\Controller\SettingBillingController;
use AppBundle\Controller\SettingMailerController;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Service;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\Financial\FinancialOverviewData;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ds\Map;

class DashboardDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        PermissionGrantedChecker $permissionGrantedChecker,
        ClientDataProvider $clientDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->options = $options;
        $this->permissionGrantedChecker = $permissionGrantedChecker;
        $this->clientDataProvider = $clientDataProvider;
    }

    /**
     * @throws \Exception
     */
    public function getOverview(): ?array
    {
        $organizationRepository = $this->entityManager->getRepository(Organization::class);

        $organizations = $organizationRepository->getAllFirstSelected();
        if (! $organizations) {
            return null;
        }

        $currentMonthPeriod = TimePeriod::createCurrentMonth();

        $financialPermission = $this->permissionGrantedChecker
            ->isGrantedSpecial(SpecialPermission::FINANCIAL_OVERVIEW);

        $clientPermission = $this->permissionGrantedChecker
            ->isGranted(Permission::VIEW, ClientController::class);

        $firstOrganizationCurrencyData = null;

        $currencies = $this->getCurrencyData($financialPermission, $organizations, $currentMonthPeriod);
        $firstOrganizationCurrencyData = $currencies->count() ? (array) $currencies->first()->value : [];
        unset($firstOrganizationCurrencyData['locale']);

        $organizationData = $this->getOrganizationsData($organizations);

        return [
            'organizations' => $organizationData,
            'overview' => [
                'clientCount' => $clientPermission
                    ? $this->clientDataProvider->getActiveCount()
                    : null,
                'clientLeadCount' => $clientPermission
                    ? $this->clientDataProvider->getLeadCount()
                    : null,
                'clientSuspendedCount' => $clientPermission && $this->options->get(Option::SUSPEND_ENABLED)
                    ? $this->clientDataProvider->getSuspendedCount()
                    : null,
                'clientOverdueCount' => $clientPermission && ! $this->options->get(Option::SUSPEND_ENABLED)
                    ? $this->clientDataProvider->getOverdueCount()
                    : null,
            ],
            'invoicingOverviewByCurrencies' => $currencies,
            // remains here for API backwards compatibility: data on first organization encountered
            'organization' => reset($organizationData),
            'invoicingOverview' => $firstOrganizationCurrencyData,
        ];
    }

    /**
     * @param Map|FinancialOverviewData[] $currencies
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function updateCurrencyData(
        Organization $organization,
        Map $currencies,
        TimePeriod $currentMonthPeriod
    ): void {
        $currency = $organization->getCurrency();
        if ($currencies->hasKey($currency)) {
            $currencyData = $currencies->get($currency);
        } else {
            $currencyData = new FinancialOverviewData();
            $currencies->put($currency, $currencyData);
        }

        $currencyData->totalDue += $this->getTotalAmountDue($organization);
        $currencyData->totalOverdue += $this->getTotalAmountOverdue(
            $organization,
            new \DateTimeImmutable()
        );
        $currencyData->invoicedThisMonth += $this->getAmountInvoicedInRange(
            $organization,
            $currentMonthPeriod
        );
        $currencyData->invoicedThisMonthUnpaid += $this->getUnpaidAmountInvoicedInRange(
            $organization,
            $currentMonthPeriod
        );
        if (! $currencyData->locale) {
            $currencyData->locale = $organization->getLocale();
        }
    }

    /**
     * @param Organization[] $organizations
     *
     * @return mixed[][]
     */
    private function getOrganizationsData(array $organizations): array
    {
        $orgData = [];
        foreach ($organizations as $organization) {
            $orgData[] = [
                'id' => $organization->getId(),
                'name' => $organization->getName(),
                'currencyCode' => $organization->getCurrency() ? $organization->getCurrency()->getCode() : null,
                'locale' => $organization->getLocale(),
            ];
        }

        return $orgData;
    }

    public function getOnboarding(): ?array
    {
        if ($this->options->getGeneral(General::ONBOARDING_HOMEPAGE_FINISHED)
            || ! (
                $this->permissionGrantedChecker->isGranted(Permission::EDIT, ClientController::class)
                && $this->permissionGrantedChecker->isGranted(Permission::EDIT, ServiceController::class)
                && $this->permissionGrantedChecker->isGranted(Permission::EDIT, SettingBillingController::class)
                && $this->permissionGrantedChecker->isGranted(Permission::EDIT, SettingApplicationController::class)
                && $this->permissionGrantedChecker->isGranted(Permission::EDIT, SettingMailerController::class)
            )
        ) {
            return null;
        }

        $clientRepository = $this->entityManager->getRepository(Client::class);
        $status = [
            'client' => $clientRepository->existsAnyNotDeleted(),
            'service' => $this->entityManager->getRepository(Service::class)->existsAnyNotDeleted(),
            'billing' => (bool) $this->options->getGeneral(General::ONBOARDING_HOMEPAGE_BILLING),
            'system' => (bool) $this->options->getGeneral(General::ONBOARDING_HOMEPAGE_SYSTEM),
        ];

        if (! $this->options->getGeneral(General::ONBOARDING_HOMEPAGE_MAILER_VIA_WIZARD)) {
            $status['mailer'] = (bool) $this->options->getGeneral(General::ONBOARDING_HOMEPAGE_MAILER);
        }

        $unfinished = array_keys(
            array_filter(
                $status,
                function (bool $value) {
                    return ! $value;
                }
            )
        );

        return [
            'status' => $status,
            'unfinishedCount' => count($unfinished),
            'first' => $unfinished ? reset($unfinished) : 'done',
            'client' => $clientRepository->getClientForNewServiceOnboarding(),
        ];
    }

    private function createTotalAmountDueQueryBuilder(Organization $organization): QueryBuilder
    {
        return $this->entityManager
            ->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('SUM(i.total - i.amountPaid)')
            ->where('i.organization = :organization')
            ->andWhere('i.invoiceStatus NOT IN(:exceptStatuses)')
            ->setParameter('organization', $organization)
            ->setParameter(
                'exceptStatuses',
                [
                    Invoice::PAID,
                    Invoice::PROFORMA_PROCESSED,
                    Invoice::VOID,
                    Invoice::DRAFT,
                ]
            );
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getTotalAmountDue(Organization $organization): float
    {
        return (float) $this->createTotalAmountDueQueryBuilder($organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getTotalAmountOverdue(Organization $organization, \DateTimeImmutable $now): float
    {
        return (float) $this->createTotalAmountDueQueryBuilder($organization)
            ->andWhere('i.dueDate <= :now')
            ->setParameter('now', $now, UtcDateTimeType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createInvoicedInRangeQueryBuilder(
        Organization $organization,
        TimePeriod $timePeriod
    ): QueryBuilder {
        return $this->entityManager
            ->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->andWhere('i.organization = :organization')
            ->andWhere('i.invoiceStatus NOT IN(:exceptStatuses)')
            ->andWhere('i.createdDate >= :from AND i.createdDate < :until')
            ->setParameter('organization', $organization)
            ->setParameter(
                'exceptStatuses',
                [
                    Invoice::VOID,
                    Invoice::DRAFT,
                ]
            )
            ->setParameter('from', $timePeriod->startDate, UtcDateTimeType::NAME)
            ->setParameter('until', $timePeriod->endDate, UtcDateTimeType::NAME);
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getAmountInvoicedInRange(
        Organization $organization,
        TimePeriod $timePeriod
    ): float {
        return (float) $this->createInvoicedInRangeQueryBuilder($organization, $timePeriod)
            ->select('SUM(i.total)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getUnpaidAmountInvoicedInRange(
        Organization $organization,
        TimePeriod $timePeriod
    ): float {
        return (float) $this->createInvoicedInRangeQueryBuilder($organization, $timePeriod)
            ->select('SUM(i.total - i.amountPaid)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCurrencyData(
        bool $financialPermission,
        array $organizations,
        TimePeriod $currentMonthPeriod
    ): Map {
        $currencies = new Map();
        if ($financialPermission) {
            foreach ($organizations as $organization) {
                $this->updateCurrencyData($organization, $currencies, $currentMonthPeriod);
            }
        }

        return $currencies;
    }
}
