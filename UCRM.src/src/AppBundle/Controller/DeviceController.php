<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Ping\PingChartDataProvider;
use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Component\Sync\SynchronizationManager;
use AppBundle\Component\WirelessStatistics\WirelessStatisticsChartDataProvider;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\DeviceInterfaceIp;
use AppBundle\Entity\DeviceIp;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Option;
use AppBundle\Entity\SearchDeviceQueue;
use AppBundle\Entity\Site;
use AppBundle\Entity\Vendor;
use AppBundle\Facade\DeviceFacade;
use AppBundle\Form\DeviceQuickType;
use AppBundle\Form\DeviceType;
use AppBundle\Grid\Device\DeviceGridFactory;
use AppBundle\Grid\DeviceInterface\DeviceInterfaceGridFactory;
use AppBundle\Grid\DeviceLog\DeviceLogGridFactory;
use AppBundle\Grid\ServiceDevice\UnknownServiceDeviceGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Encryption;
use AppBundle\Sync\Exceptions\LoginException;
use AppBundle\Util\File;
use Defuse\Crypto\Exception\CryptoException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/device")
 */
class DeviceController extends BaseController
{
    /**
     * @Route("", name="device_index")
     * @Method("GET")
     * @Permission("view")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(DeviceGridFactory::class)->create();
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'device/index.html.twig',
            [
                'devicesGrid' => $grid,
            ]
        );
    }

    /**
     * @Route("/{id}", name="device_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(Request $request, Device $device): Response
    {
        $this->notDeleted($device);

        $logGrid = $this->get(DeviceLogGridFactory::class)->create($device);
        if ($parameters = $logGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }
        $unknownServiceDevicesGrid = $this->get(UnknownServiceDeviceGridFactory::class)->create($device);
        if ($parameters = $unknownServiceDevicesGrid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        try {
            $fs = new Filesystem();
            $file = new File($this->get('kernel')->getRootDir());
            $fs->mkdir($file->getDeviceBackupDirectory($device));
            $finder = new Finder();
            $finder->files()->in($file->getDeviceBackupDirectory($device))->sortByName();
        } catch (IOException $exception) {
            // silently ignore, we can't nothing about it here and it's useless to crash whole device detail
            $finder = null;
        }

        $lastAccessibleIp = $this->em->getRepository(DeviceInterfaceIp::class)->getLastAccessibleIp($device);

        $qosDestinationGateway = $this->getOption(Option::QOS_ENABLED)
            && $this->getOption(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY;

        $decryptedLoginPassword = null;
        if ($this->isSpecialPermissionGranted(SpecialPermission::SHOW_DEVICE_PASSWORDS)) {
            try {
                $decryptedLoginPassword = $this->get(Encryption::class)->decrypt($device->getLoginPassword());
            } catch (CryptoException $exception) {
                $decryptedLoginPassword = null;
            }
        }

        return $this->render(
            'device/show.html.twig',
            [
                'device' => $device,
                'decryptedLoginPassword' => $decryptedLoginPassword,
                'inSyncQueue' => (bool) $this->em->getRepository(SearchDeviceQueue::class)->find($device),
                'logGrid' => $logGrid,
                'unknownServiceDevicesGrid' => $unknownServiceDevicesGrid,
                'outageLimit' => 10,
                'lastAccessibleIp' => $lastAccessibleIp,
                'finder' => $finder,
                'chartDataShortTerm' => $this->generateUrl(
                    'device_get_ping_chart_data',
                    [
                        'id' => $device->getId(),
                        'resolution' => PingChartDataProvider::RESOLUTION_SHORT_TERM,
                    ]
                ),
                'chartDataLongTerm' => $this->generateUrl(
                    'device_get_ping_chart_data',
                    [
                        'id' => $device->getId(),
                        'resolution' => PingChartDataProvider::RESOLUTION_LONG_TERM,
                    ]
                ),
                'wirelessStatisticsChartDataShortTerm' => $this->generateUrl(
                    'device_get_wireless_statistics_chart_data',
                    [
                        'id' => $device->getId(),
                        'resolution' => WirelessStatisticsChartDataProvider::RESOLUTION_SHORT_TERM,
                    ]
                ),
                'wirelessStatisticsChartDataLongTerm' => $this->generateUrl(
                    'device_get_wireless_statistics_chart_data',
                    [
                        'id' => $device->getId(),
                        'resolution' => WirelessStatisticsChartDataProvider::RESOLUTION_LONG_TERM,
                    ]
                ),
                'qosDestinationGateway' => $qosDestinationGateway,
            ]
        );
    }

    /**
     * @Route(
     *     "/{id}/ping-chart-data/{longTerm}",
     *     name="device_get_ping_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getPingChartDataAction(Device $device, bool $longTerm = false): JsonResponse
    {
        $chartDataProvider = $this->get(PingChartDataProvider::class);
        $data = $chartDataProvider->getDataForDevice(
            PingChartDataProvider::TYPE_NETWORK,
            $device->getId(),
            $longTerm
                ? PingChartDataProvider::RESOLUTION_LONG_TERM
                : PingChartDataProvider::RESOLUTION_SHORT_TERM
        );

        return new JsonResponse($data);
    }

    /**
     * @Route(
     *     "/{id}/wireless-statistics-chart-data/{longTerm}",
     *     name="device_get_wireless_statistics_chart_data",
     *     requirements={"id": "\d+"},
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("view")
     */
    public function getWirelessStatisticChartDataAction(Device $device, bool $longTerm = false): JsonResponse
    {
        $this->notDeleted($device);

        $wirelessStatisticDataProvider = $this->get(WirelessStatisticsChartDataProvider::class);

        $data = $wirelessStatisticDataProvider->getDataForDevice(
            WirelessStatisticsChartDataProvider::TYPE_DEVICE,
            $device->getId(),
            $longTerm
                ? WirelessStatisticsChartDataProvider::RESOLUTION_LONG_TERM
                : WirelessStatisticsChartDataProvider::RESOLUTION_SHORT_TERM
        );

        return new JsonResponse($data);
    }

    /**
     * @Route("/{id}/interfaces", name="device_show_interfaces", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showInterfacesAction(Request $request, Device $device): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, DeviceInterfaceController::class);
        $this->notDeleted($device);
        $grid = $this->get(DeviceInterfaceGridFactory::class)->create($device);
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'device/show_interfaces.html.twig',
            [
                'device' => $device,
                'inSyncQueue' => (bool) $this->em->getRepository(SearchDeviceQueue::class)->find($device),
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new/{site}", name="device_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request, Site $site = null): Response
    {
        return $this->handleNewEditAction($request, null, $site);
    }

    /**
     * @Route("/{id}/edit", name="device_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Device $device): Response
    {
        $this->notDeleted($device);

        return $this->handleNewEditAction($request, $device);
    }

    /**
     * @Route("/{id}/delete", name="device_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Device $device): Response
    {
        $this->notDeleted($device);
        if ($this->get(DeviceFacade::class)->handleDelete($device)) {
            $this->addTranslatedFlash('success', 'Device has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Device could not be deleted.');
        }

        return $this->redirectToRoute('device_index');
    }

    /**
     * @Route("/new-quick/{site}", name="device_new_quick")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newQuickAction(Request $request, Site $site = null): Response
    {
        $device = new Device();
        if ($site) {
            $device->setSite($site);
        }

        $options = [
            'action' => $this->generateUrl('device_new_quick'),
        ];

        $form = $this->createForm(DeviceQuickType::class, $device, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $vendorId = $form->get('vendorId')->getNormData();
            $vendor = $vendorId ? $this->em->find(Vendor::class, $vendorId) : null;
            if ($vendor) {
                $device->setVendor($vendor);
            } else {
                $form->get('vendorId')->addError(new FormError('This field is required.'));
            }

            $device->setLoginPassword(
                $this->get(Encryption::class)->encrypt($device->getLoginPassword())
            );

            $ipRangeField = $form->get('ipRange')->get('range');

            $deviceIp = new DeviceIp();
            $device->setSearchIp($deviceIp);

            if (! count($ipRangeField->getErrors(true)) && $ipRangeField->getData()) {
                $deviceIp->getIpRange()->setRangeFromString((string) $ipRangeField->getData());
            }

            $searchDeviceQueue = new SearchDeviceQueue();
            $searchDeviceQueue->setDevice($device);

            if ($form->isValid()) {
                $this->em->transactional(
                    function () use ($device, $searchDeviceQueue) {
                        $this->em->persist($device);
                        $this->em->flush();

                        // flush is needed twice, because SearchDeviceQueue needs the Device entity to already have ID
                        $this->em->persist($searchDeviceQueue);
                    }
                );

                $this->addTranslatedFlash('info', 'Device added to sync queue.');

                return $this->createAjaxRedirectResponse('device_index');
            }
        }

        return $this->render(
            'device/new_quick.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id}/download/{backupFileName}", name="device_backup_download")
     * @Method("GET")
     * @Permission("view")
     */
    public function downloadBackupAction(Device $device, string $backupFileName): Response
    {
        $this->notDeleted($device);

        $file = new File($this->get('kernel')->getRootDir());
        $fullFileName = sprintf('%s/%s', $file->getDeviceBackupDirectory($device), $backupFileName);

        if (! file_exists($fullFileName)) {
            $this->addTranslatedFlash('error', 'File does not exist.');

            return $this->redirectToRoute('device_index');
        }

        $message['logMsg'] = [
            'message' => 'Backup file for device %s was downloaded',
            'replacements' => $device->getName(),
        ];

        $this->get(ActionLogger::class)->log($message, $this->getUser(), null, EntityLog::DEVICE_BACKUP_DOWNLOAD);

        return $this->get(DownloadResponseFactory::class)->createFromFile(
            $fullFileName,
            $backupFileName
        );
    }

    /**
     * @Route("/{id}/add-to-sync-queue", name="search_device_add_to_sync_queue")
     * @Method({"POST", "GET"})
     * @Permission("edit")
     */
    public function addToSyncQueue(Device $device): Response
    {
        $this->notDeleted($device);

        try {
            $added = $this->get(DeviceFacade::class)->handleAddToSyncQueue($device);
        } catch (LoginException $e) {
            $this->addTranslatedFlash('warning', $e->getMessage());
        }

        if (isset($added)) {
            if ($added) {
                $this->addTranslatedFlash('info', 'Device added to sync queue.');
            } else {
                $this->addTranslatedFlash('warning', 'Device already in sync queue.');
            }
        }

        return $this->redirectToRoute(
            'device_show',
            [
                'id' => $device->getId(),
            ]
        );
    }

    private function handleNewEditAction(Request $request, Device $device = null, Site $site = null): Response
    {
        $isEdit = (bool) $device;

        if (! $isEdit) {
            $device = new Device();

            if ($site) {
                $device->setSite($site);
            }
        }
        $oldDevice = clone $device;
        $form = $this->createForm(DeviceType::class, $device);
        $loginPassword = $device->getLoginPassword();
        $decryptedLoginPassword = null;
        if ($this->isSpecialPermissionGranted(SpecialPermission::SHOW_DEVICE_PASSWORDS)) {
            try {
                $decryptedLoginPassword = $this->get(Encryption::class)->decrypt($loginPassword);
            } catch (CryptoException $exception) {
                $decryptedLoginPassword = null;
            }
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $httpData = $request->request->get('device');
            if (! isset($httpData['loginPassword'])) {
                // If user does not fill new password, we must use old password from original entity.
                // Form request handler would set the password as NULL, because it's not present in POST.
                $device->setLoginPassword($loginPassword);
            } else {
                $device->setLoginPassword(
                    $this->get(Encryption::class)->encrypt($device->getLoginPassword())
                );
            }

            $device->setSynchronized(false);

            if ($device->getQosEnabled() !== BaseDevice::QOS_ANOTHER) {
                $device->getQosDevices()->clear();
            }

            if ($isEdit) {
                $this->get(QoSSynchronizationManager::class)->unsynchronizeDevice($device, $oldDevice);
            } else {
                $this->get(QoSSynchronizationManager::class)->unsynchronizeDevice($device);
                $this->em->persist($device);
            }

            $this->get(SynchronizationManager::class)->unsynchronizeSuspend();

            $this->em->flush();

            $this->addTranslatedFlash('success', $isEdit ? 'Device has been saved.' : 'Device has been created.');

            return $this->redirectToRoute('device_show', ['id' => $device->getId()]);
        }

        $qosDestinationGateway = $this->getOption(Option::QOS_ENABLED)
            && $this->getOption(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY;

        return $this->render(
            sprintf('device/%s.html.twig', $isEdit ? 'edit' : 'new'),
            [
                'device' => $device,
                'form' => $form->createView(),
                'qosDestinationGateway' => $qosDestinationGateway,
                'decryptedLoginPassword' => $decryptedLoginPassword,
            ]
        );
    }
}
