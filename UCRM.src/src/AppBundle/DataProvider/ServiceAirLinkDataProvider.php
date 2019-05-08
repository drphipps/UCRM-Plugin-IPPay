<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\AirLink\AirLinkUrlGenerator;
use AppBundle\Entity\Service;
use AppBundle\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class ServiceAirLinkDataProvider extends AbstractAirLinkDataProvider
{
    /**
     * @var AirLinkUrlGenerator
     */
    private $airLinkUrlGenerator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(AirLinkUrlGenerator $airLinkUrlGenerator, EntityManagerInterface $entityManager)
    {
        $this->airLinkUrlGenerator = $airLinkUrlGenerator;
        $this->entityManager = $entityManager;
    }

    public function get(Service $service): ?string
    {
        $cpe = $service->getGpsCoordinate();
        if (! $cpe) {
            return null;
        }

        $sites = [];
        foreach ($service->getServiceDevices() as $serviceDevice) {
            $site = $serviceDevice->getInterface()->getDevice()->getSite();
            if (! array_key_exists($site->getId(), $sites)) {
                $sites[$site->getId()] = $site;
            }
        }

        if (! $sites && $service->getClient()->getIsLead()) {
            $sites = $this->entityManager->getRepository(Site::class)->findBy(
                [
                    'deletedAt' => null,
                ]
            );
        }

        $site = $this->getNearestSite($sites, $cpe);
        if (! $site) {
            return null;
        }

        return $this->airLinkUrlGenerator->get($site->getGpsCoordinate(), [$cpe]);
    }
}
