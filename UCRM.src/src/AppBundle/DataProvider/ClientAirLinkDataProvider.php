<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Component\AirLink\AirLinkUrlGenerator;
use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class ClientAirLinkDataProvider extends AbstractAirLinkDataProvider
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

    public function get(Client $client, ?Service $service = null): ?string
    {
        $cpe = ($service ? $service->getGpsCoordinate() : null) ?? $client->getGpsCoordinate();
        if (! $cpe) {
            return null;
        }

        $sites = $this->entityManager->getRepository(Site::class)->findBy(
            [
                'deletedAt' => null,
            ]
        );
        $site = $this->getNearestSite($sites, $cpe);
        if (! $site) {
            return null;
        }

        return $this->airLinkUrlGenerator->get($site->getGpsCoordinate(), [$cpe]);
    }
}
