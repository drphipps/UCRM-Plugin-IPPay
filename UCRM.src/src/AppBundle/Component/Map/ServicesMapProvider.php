<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Map;

use AppBundle\Component\Map\Element\ServicePoint;
use AppBundle\Component\Map\Element\ServiceSiteLine;
use AppBundle\Component\Map\Element\SitePoint;
use AppBundle\Entity\Service;
use AppBundle\Entity\Site;
use AppBundle\Util\Strings;
use Doctrine\ORM\EntityNotFoundException;

class ServicesMapProvider
{
    /**
     * @param Service[] $services
     */
    public function getData(array $services, bool $includeSites = true): array
    {
        $sitePoints = [];
        $servicePoints = [];
        $lines = [];

        foreach ($services as $service) {
            /** @var Site[] $connectedSites */
            $connectedSites = [];
            foreach ($service->getServiceDevices() as $serviceDevice) {
                try {
                    $site = $serviceDevice->getInterface()->getDevice()->getSite();
                } catch (EntityNotFoundException $exception) {
                    continue;
                }

                if (array_key_exists($site->getId(), $connectedSites)) {
                    continue;
                }

                $connectedSites[$site->getId()] = $site;
            }

            if (null !== $service->getAddressGpsLat() && null !== $service->getAddressGpsLon()) {
                $servicePoints[$service->getId()] = new ServicePoint(
                    $service->getId(),
                    $service->getAddressGpsLat(),
                    $service->getAddressGpsLon(),
                    sprintf(
                        '<strong>%s</strong><br>%s',
                        htmlspecialchars($service->getName() ?? '', ENT_QUOTES),
                        Strings::wrapAddress($service->getAddress())
                    ),
                    $connectedSites ? array_keys($connectedSites) : []
                );
            }

            if (! $includeSites) {
                continue;
            }

            foreach ($connectedSites as $site) {
                if (! array_key_exists($site->getId(), $sitePoints)) {
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

                if (array_key_exists($service->getId(), $servicePoints)) {
                    $lines[$site->getId()][$service->getId()] = new ServiceSiteLine(
                        $servicePoints[$service->getId()],
                        $sitePoints[$site->getId()]
                    );
                }
            }
        }

        return [
            'sites' => $sitePoints,
            'services' => $servicePoints,
            'lines' => $lines,
        ];
    }
}
