<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Country;
use AppBundle\Entity\State;
use AppBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * State controller.
 *
 * @Route("/state")
 */
class StateController extends BaseController
{
    /**
     * @Route("/states-in-country/{id}", name="states_in_country", options={"expose"=true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     */
    public function getStateInCountryAction(Country $country): JsonResponse
    {
        /** @var State[] $states */
        $states = $this->em->getRepository(State::class)->findBy(
            [
                'country' => $country,
            ],
            [
                'name' => 'asc',
            ]
        );

        $result = [];
        foreach ($states as $state) {
            $result[] = ['id' => $state->getId(), 'name' => $state->getName()];
        }

        return new JsonResponse(['states' => $result]);
    }
}
