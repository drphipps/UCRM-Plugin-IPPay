<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\RefundMap;
use ApiBundle\Mapper\RefundMapper;
use ApiBundle\Request\RefundCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\RefundController as AppRefundController;
use AppBundle\DataProvider\RefundDataProvider;
use AppBundle\Entity\Refund;
use AppBundle\Facade\RefundFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Helpers;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppRefundController::class)
 */
class RefundController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var RefundFacade
     */
    private $facade;

    /**
     * @var RefundMapper
     */
    private $mapper;

    /**
     * @var RefundDataProvider
     */
    private $refundDataProvider;

    public function __construct(
        Validator $validator,
        RefundFacade $facade,
        RefundMapper $mapper,
        RefundDataProvider $refundDataProvider
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->refundDataProvider = $refundDataProvider;
    }

    /**
     * @Get("/refunds/{id}", name="refund_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(Refund $refund): View
    {
        return $this->view(
            $this->mapper->reflect($refund)
        );
    }

    /**
     * @Delete(
     *     "/refunds/{id}",
     *     name="refund_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(Refund $refund): View
    {
        if (! $this->facade->handleDelete($refund)) {
            throw new HttpException(422, 'Refund could not be deleted.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Get(
     *     "/refunds",
     *     name="refunds_collection_get",
     *     options={"method_prefix"=false},
     * )
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="createdDateFrom",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection starting on date (including)"
     * )
     * @QueryParam(
     *     name="createdDateTo",
     *     requirements="\d{4}-\d{2}-\d{2}",
     *     strict=true,
     *     nullable=true,
     *     description="limit collection ending on date (including)"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="max results limit"
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     strict=true,
     *     nullable=true,
     *     description="results offset"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $refundCollectionRequest = new RefundCollectionRequest();

        if ($startDate = $paramFetcher->get('createdDateFrom')) {
            try {
                $refundCollectionRequest->startDate = DateTimeFactory::createDate($startDate);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }
        if ($endDate = $paramFetcher->get('createdDateTo')) {
            try {
                $refundCollectionRequest->endDate = DateTimeFactory::createDate($endDate)->setTime(23, 59, 59);
            } catch (\InvalidArgumentException $exception) {
                throw new HttpException(400, $exception->getMessage(), $exception);
            }
        }

        $refundCollectionRequest->offset = Helpers::typeCastNullable('int', $paramFetcher->get('offset'));
        $refundCollectionRequest->limit = Helpers::typeCastNullable('int', $paramFetcher->get('limit'));

        return $this->view(
            $this->mapper->reflectCollection(
                $this->refundDataProvider->getCollection($refundCollectionRequest)
            )
        );
    }

    /**
     * @Post("/refunds", name="refund_add", options={"method_prefix"=false})
     * @ParamConverter("refundMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(RefundMap $refundMap, string $version): View
    {
        $refund = new Refund();
        $refund->setCreatedDate(new \DateTime());

        $this->mapper->map($refundMap, $refund);
        $this->validator->validate($refund, $this->mapper->getFieldsDifference());

        if ($refund->getClient() && $refund->getClient()->getIsLead()) {
            throw new ValidationHttpException([], 'This action is not possible, while the client is lead.');
        }

        $this->facade->handleCreate($refund);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($refund),
            'api_refund_get',
            [
                'version' => $version,
                'id' => $refund->getId(),
            ]
        );
    }
}
