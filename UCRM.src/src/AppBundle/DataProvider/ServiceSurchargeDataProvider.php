<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use Doctrine\ORM\EntityManager;

class ServiceSurchargeDataProvider
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAll(Service $service): array
    {
        return $this->em->getRepository(ServiceSurcharge::class)
            ->findBy(
                [
                    'service' => $service,
                ],
                [
                    'id' => 'ASC',
                ]
            );
    }
}
