<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientTag;
use AppBundle\Entity\Device;
use AppBundle\Entity\Mailing;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Site;
use AppBundle\Entity\Tariff;
use AppBundle\Form\Data\MailingFilterData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class MailingDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        EntityManager $em
    ) {
        $this->em = $em;
    }

    public function getMailingGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Mailing::class)->getMailingQueryBuilder()
            ->leftJoin('m.emailLogs', 'el')
            ->addGroupBy('m.id');
    }

    /**
     * @return Client[]
     */
    public function getMailingPreviewData(?array $filter, ?array $clientId): array
    {
        return $this->em->getRepository(Client::class)->getMailingPreviewData(
            $filter['organization'] ?? null,
            $filter['clientType'] ?? null,
            $filter['clientTag'] ?? null,
            $filter['servicePlan'] ?? null,
            $filter['periodStartDay'] ?? null,
            $filter['site'] ?? null,
            $filter['device'] ?? null,
            $filter['includeLeads'] ?? null,
            $clientId
        );
    }

    /**
     * @return Client[]
     */
    public function getClients(array $clientIds): array
    {
        return $this->em->getRepository(Client::class)->getClients($clientIds);
    }

    public function getFilterDataEntities(MailingFilterData $mailingFilterData, array $sessionData): MailingFilterData
    {
        $mailingFilterData->filterOrganizations = $this->getEntityArrayCollection(Organization::class, $sessionData['organization'] ?? []);
        $mailingFilterData->filterClientTypes = $sessionData['clientType'] ?? [];
        $mailingFilterData->filterClientTags = $this->getEntityArrayCollection(ClientTag::class, $sessionData['clientTag'] ?? []);
        $mailingFilterData->filterServicePlans = $this->getEntityArrayCollection(Tariff::class, $sessionData['servicePlan'] ?? []);
        $mailingFilterData->filterPeriodStartDays = $sessionData['periodStartDay'] ?? [];
        $mailingFilterData->filterSites = $this->getEntityArrayCollection(Site::class, $sessionData['site'] ?? []);
        $mailingFilterData->filterDevices = $this->getEntityArrayCollection(Device::class, $sessionData['device'] ?? []);
        $mailingFilterData->filterIncludeLeads = $sessionData['includeLeads'] ?? null;

        return $mailingFilterData;
    }

    private function getEntityArrayCollection($entityName, array $id): ArrayCollection
    {
        return new ArrayCollection($this->em->getRepository($entityName)->findBy(['id' => $id]));
    }
}
