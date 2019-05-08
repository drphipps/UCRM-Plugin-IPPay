<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Component\Validator\Validator;
use ApiBundle\Map\PaymentPlanMap;
use ApiBundle\Mapper\PaymentPlanMapper;
use ApiBundle\Request\PaymentPlanCollectionRequest;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Controller\PaymentController;
use AppBundle\DataProvider\PaymentPlanDataProvider;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Options;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(PaymentController::class)
 */
class PaymentPlanController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var PaymentPlanFacade
     */
    private $facade;

    /**
     * @var PaymentPlanDataProvider
     */
    private $dataProvider;

    /**
     * @var PaymentPlanMapper
     */
    private $mapper;

    /**
     * @var Options
     */
    private $options;

    public function __construct(
        Validator $validator,
        PaymentPlanFacade $facade,
        PaymentPlanDataProvider $dataProvider,
        PaymentPlanMapper $mapper,
        Options $options
    ) {
        $this->validator = $validator;
        $this->facade = $facade;
        $this->dataProvider = $dataProvider;
        $this->mapper = $mapper;
        $this->options = $options;
    }

    /**
     * @Get("/payment-plans/{id}", name="payment_plan_get", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getAction(PaymentPlan $paymentPlan): View
    {
        $this->requireRecurringPaymentsEnabled();

        return $this->view(
            $this->mapper->reflect($paymentPlan)
        );
    }

    /**
     * @Get("/payment-plans", name="payment_plan_collection_get", options={"method_prefix"=false})
     * @ViewHandler()
     * @Permission("view")
     */
    public function getCollectionAction(): View
    {
        $this->requireRecurringPaymentsEnabled();

        $paymentPlans = $this->dataProvider->getCollection(new PaymentPlanCollectionRequest());

        return $this->view(
            $this->mapper->reflectCollection($paymentPlans)
        );
    }

    /**
     * @Post("/payment-plans", name="payment_plan_add", options={"method_prefix"=false})
     * @ParamConverter("paymentPlanMap", converter="fos_rest.request_body")
     * @ViewHandler()
     * @Permission("edit")
     */
    public function postAction(PaymentPlanMap $paymentPlanMap, string $version): View
    {
        $this->requireRecurringPaymentsEnabled();

        $paymentPlan = new PaymentPlan();
        $this->mapper->map($paymentPlanMap, $paymentPlan);
        $this->validator->validate($paymentPlan, $this->mapper->getFieldsDifference());

        if ($paymentPlanMap->provider !== PaymentPlan::PROVIDER_IPPAY) {
            throw new BadRequestHttpException('Only IPPay subscriptions can be added.');
        }

        $this->facade->handleCreateActive($paymentPlan);

        return $this->routeRedirectViewWithData(
            $this->mapper->reflect($paymentPlan),
            'api_payment_plan_get',
            [
                'version' => $version,
                'id' => $paymentPlan->getId(),
            ]
        );
    }

    /**
     * @Patch("/payment-plans/{id}/cancel", name="payment_plan_cancel", options={"method_prefix"=false}, requirements={"id": "\d+"})
     * @ViewHandler()
     * @Permission("edit")
     */
    public function cancelAction(PaymentPlan $paymentPlan)
    {
        try {
            $this->facade->cancelSubscription($paymentPlan);
        } catch (\RuntimeException $exception) {
            throw new HttpException(422, $exception->getMessage());
        }

        return $this->view(
            $this->mapper->reflect($paymentPlan)
        );
    }

    private function requireRecurringPaymentsEnabled(): void
    {
        if (
            ! $this->options->get(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && ! $this->options->get(Option::SUBSCRIPTIONS_ENABLED_LINKED)
        ) {
            throw new BadRequestHttpException('Recurring payments are disabled.');
        }
    }
}
