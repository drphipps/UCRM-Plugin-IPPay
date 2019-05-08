<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\CountryMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Entity\Country;
use AppBundle\Facade\CountryFacade;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 */
class CountryController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var CountryFacade
     */
    private $facade;

    /**
     * @var CountryMapper
     */
    private $mapper;

    public function __construct(CountryFacade $facade, CountryMapper $mapper)
    {
        $this->facade = $facade;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/countries/{id}", name="country_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(Country $country): View
    {
        return $this->view(
            $this->mapper->reflect($country)
        );
    }

    /**
     * @Get("/countries", name="country_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getCollectionAction(): View
    {
        $countries = $this->facade->getAllCountries();

        return $this->view(
            $this->mapper->reflectCollection($countries)
        );
    }
}
