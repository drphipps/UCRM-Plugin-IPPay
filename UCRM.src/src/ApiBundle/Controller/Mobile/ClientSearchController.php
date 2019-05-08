<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller\Mobile;

use ApiBundle\Controller\BaseController;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\ClientController;
use AppBundle\DataProvider\ClientSearchDataProvider;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Prefix("/mobile/clients")
 * @Rest\NamePrefix("api_mobile_")
 * @PermissionControllerName(ClientController::class)
 */
class ClientSearchController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ClientSearchDataProvider
     */
    private $clientSearchDataProvider;

    public function __construct(ClientSearchDataProvider $clientSearchDataProvider)
    {
        $this->clientSearchDataProvider = $clientSearchDataProvider;
    }

    /**
     * @Rest\Get("/search", name="client_search")
     * @Rest\View()
     * @Permission("view")
     * @Rest\QueryParam(
     *     name="query",
     *     requirements=@Assert\NotBlank(),
     *     strict=true,
     *     nullable=false,
     *     description="search string"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $results = $this->clientSearchDataProvider->getClients($paramFetcher->get('query'));

        if ($results === null) {
            throw new HttpException(503, 'Elasticsearch is down or returned an invalid response. Try again later.');
        }

        return $this->view($results);
    }
}
