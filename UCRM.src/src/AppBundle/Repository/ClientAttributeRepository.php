<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientAttribute;

class ClientAttributeRepository extends BaseRepository
{
    public function loadAttributes(Client $client): void
    {
        $this
            ->loadRelatedEntities(
                'attribute',
                $client
                    ->getAttributes()
                    ->map(
                        function (ClientAttribute $attribute) {
                            return $attribute->getId();
                        }
                    )
                    ->toArray()
            );
    }
}
