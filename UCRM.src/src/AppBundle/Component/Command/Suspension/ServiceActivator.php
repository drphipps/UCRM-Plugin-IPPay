<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Suspension;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Facade\ServiceSuspensionFacade;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\ServiceStatusUpdater;

class ServiceActivator
{
    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var ServiceDataProvider
     */
    private $serviceDataProvider;

    /**
     * @var ServiceSuspensionFacade
     */
    private $serviceSuspensionFacade;

    public function __construct(
        ServiceStatusUpdater $serviceStatusUpdater,
        ClientStatusUpdater $clientStatusUpdater,
        ServiceDataProvider $serviceDataProvider,
        ServiceSuspensionFacade $serviceSuspensionFacade
    ) {
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->serviceDataProvider = $serviceDataProvider;
        $this->serviceSuspensionFacade = $serviceSuspensionFacade;
    }

    public function activate(): void
    {
        $this->serviceSuspensionFacade->activateServices(
            $this->serviceDataProvider->getServicesPreparedForActivation()
        );
        $this->serviceStatusUpdater->updateServices();
        $this->clientStatusUpdater->update();
    }
}
