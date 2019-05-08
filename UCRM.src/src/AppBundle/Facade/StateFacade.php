<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Country;
use AppBundle\Entity\State;
use Doctrine\ORM\EntityManager;

class StateFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllStates(Country $country): array
    {
        $repository = $this->em->getRepository(State::class);
        $states = $repository->findBy(
            [
                'country' => $country,
            ],
            [
                'name' => 'ASC',
                'id' => 'ASC',
            ]
        );

        return $states;
    }
}
