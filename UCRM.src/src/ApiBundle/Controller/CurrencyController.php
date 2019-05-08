<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\CurrencyMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\DataProvider\CurrencyDataProvider;
use AppBundle\Entity\Currency;
use AppBundle\Security\Permission;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * @NamePrefix("api_")
 */
class CurrencyController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var CurrencyDataProvider
     */
    private $dataProvider;

    /**
     * @var CurrencyMapper
     */
    private $mapper;

    public function __construct(CurrencyDataProvider $dataProvider, CurrencyMapper $mapper)
    {
        $this->dataProvider = $dataProvider;
        $this->mapper = $mapper;
    }

    /**
     * @Get("/currencies/{id}", name="currency_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getAction(Currency $currency): View
    {
        return $this->view(
            $this->mapper->reflect($currency)
        );
    }

    /**
     * @Get("/currencies", name="currency_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("public")
     */
    public function getCollectionAction(): View
    {
        return $this->view(
            $this->mapper->reflectCollection($this->dataProvider->getAllCurrencies())
        );
    }
}
