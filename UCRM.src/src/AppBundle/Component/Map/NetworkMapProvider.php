<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map;

use AppBundle\Component\Map\Element\LeadPoint;
use AppBundle\Component\Map\Element\ServicePoint;
use AppBundle\Component\Map\Element\ServiceSiteLine;
use AppBundle\Component\Map\Element\SitePoint;
use AppBundle\Component\Map\Request\NetworkMapRequest;
use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\Site;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class NetworkMapProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getData(NetworkMapRequest $networkMapRequest): array
    {
        if ($networkMapRequest->sites) {
            $sites = $networkMapRequest->sites;
            $services = $this->entityManager->getRepository(Service::class)->getAllForMap($sites, $networkMapRequest->clientLead);
        } else {
            $sites = $this->entityManager->getRepository(Site::class)->findBy(
                [
                    'deletedAt' => null,
                ]
            );
            $services = $this->entityManager->getRepository(Service::class)->getAllForMap([], $networkMapRequest->clientLead);
        }

        $sitePoints = [];
        $servicePoints = [];
        $lines = [];

        foreach ($sites as $site) {
            $sitePoints[$site->getId()] = new SitePoint(
                $site->getId(),
                (float) $site->getGpsLat(),
                (float) $site->getGpsLon(),
                sprintf(
                    '<strong>%s</strong><br>%s',
                    htmlspecialchars($site->getName() ?? '', ENT_QUOTES),
                    Strings::wrapAddress($site->getAddress())
                )
            );
        }

        foreach ($services as $service) {
            if (! $service->getAddressGpsLat() || ! $service->getAddressGpsLon()) {
                continue;
            }

            $connectedSiteIds = [];
            foreach ($service->getServiceDevices() as $serviceDevice) {
                try {
                    $connectedSiteIds[] = $serviceDevice->getInterface()->getDevice()->getSite()->getId();
                } catch (EntityNotFoundException $exception) {
                    continue;
                }
            }
            $connectedSiteIds = array_unique($connectedSiteIds);

            $isLeadService = $networkMapRequest->clientLead
                && $networkMapRequest->clientLead->getId() === $service->getClient()->getId();

            $servicePoints[$service->getId()] = new ServicePoint(
                $service->getId(),
                $service->getAddressGpsLat(),
                $service->getAddressGpsLon(),
                sprintf(
                    '<strong>%s - %s</strong><br>%s',
                    htmlspecialchars($service->getClient()->getNameForView() ?? '', ENT_QUOTES),
                    htmlspecialchars($service->getName() ?? '', ENT_QUOTES),
                    Strings::wrapAddress($service->getAddress())
                ),
                $connectedSiteIds ?: [],
                $isLeadService ? 'lead' : 'service'
            );

            foreach ($connectedSiteIds as $connectedSiteId) {
                if (! array_key_exists($connectedSiteId, $sitePoints)) {
                    continue;
                }
                $lines[$connectedSiteId][$service->getId()] = new ServiceSiteLine(
                    $servicePoints[$service->getId()],
                    $sitePoints[$connectedSiteId]
                );
            }
        }

        if ($networkMapRequest->excludeLeads) {
            $leads = [];
        } elseif ($networkMapRequest->clientLead) {
            $leads = [$networkMapRequest->clientLead];
        } else {
            $leads = $this->entityManager->getRepository(Client::class)->findBy(
                [
                    'deletedAt' => null,
                    'isLead' => true,
                ]
            );
        }
        $leadPoints = [];
        foreach ($leads as $lead) {
            if (! $lead->getAddressGpsLat() || ! $lead->getAddressGpsLon()) {
                continue;
            }

            $leadPoints[$lead->getId()] = new LeadPoint(
                $lead->getId(),
                $lead->getAddressGpsLat(),
                $lead->getAddressGpsLon(),
                sprintf(
                    '<strong>%s</strong><br>%s',
                    htmlspecialchars($lead->getNameForView() ?? '', ENT_QUOTES),
                    Strings::wrapAddress($lead->getAddress())
                )
            );
        }

        return [
            'sites' => $sitePoints,
            'services' => $servicePoints,
            'leads' => $leadPoints,
            'lines' => $lines,
        ];
    }
}
