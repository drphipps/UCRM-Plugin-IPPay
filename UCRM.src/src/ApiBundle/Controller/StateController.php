<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\StateMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Entity\Country;
use AppBundle\Entity\State;
use AppBundle\Facade\StateFacade;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 */
class StateController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var StateFacade
     */
    private $facade;

    /**
     * @var StateMapper
     */
    private $mapper;

    public function __construct(StateFacade $facade, StateMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/countries/states/{id}", name="state_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(State $state): View
    {
        return $this->view(
            $this->mapper->reflect($state)
        );
    }

    /**
     * @Get(
     *     "/countries/{id}/states",
     *     name="state_collection_get",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("public")
     */
    public function getCollectionAction(Country $country): View
    {
        $states = $this->facade->getAllStates($country);

        return $this->view(
            $this->mapper->reflectCollection($states)
        );
    }
}
