<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Site;
use Location\Coordinate;
use Location\Distance\Haversine;

abstract class AbstractAirLinkDataProvider
{
    /**
     * @param Site[] $sites
     */
    protected function getNearestSite(array $sites, Coordinate $cpe): ?Site
    {
        if (! $sites) {
            return null;
        }

        $haversine = new Haversine();
        usort(
            $sites,
            function (Site $siteA, Site $siteB) use ($cpe, $haversine) {
                $a = $siteA->getGpsCoordinate();
                $b = $siteB->getGpsCoordinate();

                if (! $a && ! $b) {
                    return 0;
                }

                if (! $a) {
                    return 1;
                }

                if (! $b) {
                    return -1;
                }

                return ($a->getDistance($cpe, $haversine)) <=> ($b->getDistance($cpe, $haversine));
            }
        );

        /** @var Site $site */
        $site = reset($sites);

        return $site->getGpsCoordinate() ? $site : null;
    }
}
