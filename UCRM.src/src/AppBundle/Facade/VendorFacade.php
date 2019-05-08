<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class VendorFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllVendors(): array
    {
        $repository = $this->em->getRepository(Vendor::class);

        return $repository->findBy(
            [],
            [
                'id' => 'ASC',
            ]
        );
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Vendor::class)->createQueryBuilder('v');
    }
}
