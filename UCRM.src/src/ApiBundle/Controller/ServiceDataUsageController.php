<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Controller;

use ApiBundle\Mapper\ServiceUsageMapper;
use ApiBundle\Security\AppKeyAuthenticatedInterface;
use AppBundle\Component\NetFlow\TableDataProvider;
use AppBundle\Controller\ServiceController as AppServiceController;
use AppBundle\DataProvider\ServiceDataUsageProvider;
use AppBundle\Entity\Service;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Util\DateTimeImmutableFactory;
use AppBundle\Util\UnitConverter\BinaryConverter;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View as ViewHandler;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @NamePrefix("api_")
 * @PermissionControllerName(AppServiceController::class)
 */
class ServiceDataUsageController extends BaseController implements AppKeyAuthenticatedInterface
{
    /**
     * @var ServiceUsageMapper
     */
    private $usageMapper;

    /**
     * @var ServiceDataUsageProvider
     */
    private $serviceDataUsageProvider;

    /**
     * @var TableDataProvider
     */
    private $tableDataProvider;

    public function __construct(
        ServiceUsageMapper $usageMapper,
        ServiceDataUsageProvider $serviceDataUsageProvider,
        TableDataProvider $tableDataProvider
    ) {
        $this->usageMapper = $usageMapper;
        $this->serviceDataUsageProvider = $serviceDataUsageProvider;
        $this->tableDataProvider = $tableDataProvider;
    }

    /**
     * @Get(
     *     "/clients/services/{id}/data-usage/{periodStart}",
     *     name="service_data_usage",
     *     options={"method_prefix"=false},
     *     requirements={"id": "\d+"}
     * )
     * @ViewHandler()
     * @Permission("view")
     */
    public function dataUsageAction(Service $service, \DateTime $periodStart): View
    {
        $period = $this->tableDataProvider->findPeriod($service, DateTimeImmutableFactory::createFromInterface($periodStart));

        if (! $period) {
            throw new NotFoundHttpException(sprintf('Period starting on %s not found.', $periodStart->format('Y-m-d')));
        }

        return $this->view(
            $this->usageMapper->reflect(
                $this->serviceDataUsageProvider->getDataInPeriod(
                    $service,
                    $period,
                    BinaryConverter::UNIT_BYTE
                )
            )
        );
    }
}
