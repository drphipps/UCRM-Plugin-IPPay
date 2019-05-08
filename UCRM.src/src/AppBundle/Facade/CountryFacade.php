<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Country;
use Doctrine\ORM\EntityManager;

class CountryFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllCountries(): array
    {
        $repository = $this->em->getRepository(Country::class);
        $countries = $repository->findBy(
            [],
            [
                'name' => 'ASC',
            ]
        );

        return $countries;
    }
}
