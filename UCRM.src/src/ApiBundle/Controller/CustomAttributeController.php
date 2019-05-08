<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\CustomAttributeMap;
use ApiBundle\Mapper\CustomAttributeMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\CustomAttributeController as AppCustomAttributeController;
use AppBundle\DataProvider\CustomAttributeDataProvider;
use AppBundle\Entity\CustomAttribute;
use AppBundle\Facade\CustomAttributeFacade;
use AppBundle\Factory\CustomAttributeFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\Helpers;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppCustomAttributeController::class)
 */
class CustomAttributeController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var CustomAttributeFacade
     */
    private $facade;

    /**
     * @var CustomAttributeMapper
     */
    private $mapper;

    /**
     * @var CustomAttributeDataProvider
     */
    private $dataProvider;

    /**
     * @var CustomAttributeFactory
     */
    private $customAttributeFactory;

    public function __construct(
        Validator $validator,
        CustomAttributeFacade $facade,
        CustomAttributeMapper $mapper,
        CustomAttributeDataProvider $dataProvider,
        CustomAttributeFactory $customAttributeFactory
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->mapper = $mapper;
        $this->dataProvider = $dataProvider;
        $this->customAttributeFactory = $customAttributeFactory;
    }

    /**
     * @Get("/custom-attributes/{id}", name="custom_attribute_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(CustomAttribute $customAttribute): View
    {
        return $this->view(
            $this->mapper->reflect($customAttribute)
        );
    }

    /**
     * @Delete(
     *     "/custom-attributes/{id}",
     *     name="custom_attribute_delete",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("edit")
     */
    public function deleteAction(CustomAttribute $customAttribute): View
    {
        try {
            $this->facade->handleDelete($customAttribute);
        } catch (ForeignKeyConstraintViolationException  $e) {
            throw new HttpException(422, 'Cannot be deleted. Item is used.');
        }

        return $this->view(null, 200);
    }

    /**
     * @Get("/custom-attributes", name="custom_attribute_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     * @QueryParam(
     *     name="attributeType",
     *     requirements="client|invoice",
     *     strict=true,
     *     nullable=true,
     *     description="select only attributes of given type (client|invoice)"
     * )
     */
    public function getCollectionAction(ParamFetcherInterface $paramFetcher): View
    {
        $attributeType = Helpers::typeCastNullable('string', $paramFetcher->get('attributeType'));
        if ($attributeType) {
            $customAttributes = $this->dataProvider->getByAttributeType($attributeType);
        } else {
            $customAttributes = $this->dataProvider->getAll();
        }

        return $this->view(
            $this->mapper->reflectCollection($customAttributes)
        );
    }

    /**
     * @Patch("/custom-attributes/{id}", name="custom_attribute_edit", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ParamConverter("customAttributeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function patchAction(CustomAttribute $customAttribute, CustomAttributeMap $customAttributeMap): View
    {
        $this->mapper->map($customAttributeMap, $customAttribute);
        $this->validator->validate($customAttribute, $this->mapper->getFieldsDifference());
        $this->facade->handleEdit($customAttribute);

        return $this->view(
            $this->mapper->reflect($customAttribute)
        );
    }

    /**
     * @Post("/custom-attributes", name="custom_attribute_add", options={"method_prefix"=false})
     * @ParamConverter("customAttributeMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(CustomAttributeMap $customAttributeMap, string $version): View
    {
        $customAttribute = $this->customAttributeFactory->create(CustomAttribute::TYPE_STRING, null);
        $this->mapper->map($customAttributeMap, $customAttribute);
        $this->validator->validate($customAttribute, $this->mapper->getFieldsDifference());
        $this->facade->handleNew($customAttribute);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($customAttribute),
            'api_custom_attribute_get',
            [
                'version' => $version,
                'id' => $customAttribute->getId(),
            ]
        );
    }
}
