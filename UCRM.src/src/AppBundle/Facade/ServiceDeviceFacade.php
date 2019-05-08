<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\SearchServiceDeviceQueue;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Event\ServiceDevice\ServiceDeviceAddEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceDeleteEvent;
use AppBundle\Event\ServiceDevice\ServiceDeviceEditEvent;
use AppBundle\Service\Encryption;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceDeviceFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @var QoSSynchronizationManager
     */
    private $qosSynchronizationManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManager $em,
        Encryption $encryption,
        QoSSynchronizationManager $qosSynchronizationManager,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->em = $em;
        $this->encryption = $encryption;
        $this->qosSynchronizationManager = $qosSynchronizationManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function setDefaults(Service $service, ServiceDevice $serviceDevice)
    {
        $serviceDevice->setService($service);
    }

    public function handleCreate(ServiceDevice $serviceDevice)
    {
        if ($serviceDevice->getLoginPassword()) {
            $serviceDevice->setLoginPassword(
                $this->encryption->encrypt($serviceDevice->getLoginPassword())
            );
        }

        $this->transactionDispatcher->transactional(
            function (EntityManager $entityManager) use ($serviceDevice) {
                $this->handleCreateUpdate($serviceDevice);
                $serviceDevice->getService()->addServiceDevice($serviceDevice);
                $entityManager->persist($serviceDevice);
                yield new ServiceDeviceAddEvent($serviceDevice);
            }
        );
    }

    public function handleUpdate(ServiceDevice $serviceDevice, ServiceDevice $oldServiceDevice)
    {
        $loginPassword = $serviceDevice->getLoginPassword();
        if (null === $loginPassword) {
            // If user does not fill new password, we must use old password from original entity.
            $serviceDevice->setLoginPassword($oldServiceDevice->getLoginPassword());
        } else {
            $serviceDevice->setLoginPassword(
                $this->encryption->encrypt($loginPassword)
            );
        }

        $this->transactionDispatcher->transactional(
            function () use ($serviceDevice, $oldServiceDevice) {
                $this->handleCreateUpdate($serviceDevice, $oldServiceDevice);
                yield new ServiceDeviceEditEvent($serviceDevice, $oldServiceDevice);
            }
        );
    }

    public function handleDelete(ServiceDevice $serviceDevice)
    {
        if (null !== $serviceDevice->getManagementIpAddress() || ! $serviceDevice->getServiceIps()->isEmpty()) {
            $this->qosSynchronizationManager->unsynchronizeDevice($serviceDevice);
        }

        $this->transactionDispatcher->transactional(
            function (EntityManager $entityManager) use ($serviceDevice) {
                $serviceDevice->getService()->removeServiceDevice($serviceDevice);
                $entityManager->remove($serviceDevice);
                yield new ServiceDeviceDeleteEvent($serviceDevice);
            }
        );
    }

    public function handleAttachServiceDevice(ServiceDevice $serviceDevice, Service $service): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($serviceDevice, $service) {
                $serviceDeviceBeforeUpdate = clone $serviceDevice;
                $serviceDevice->setService($service);
                $service->addServiceDevice($serviceDevice);

                yield new ServiceDeviceEditEvent($serviceDevice, $serviceDeviceBeforeUpdate);
            }
        );
    }

    /**
     * @param array|ServiceDevice[] $devices
     *
     * @return array [$synced, $alreadySynced]
     */
    public function handleAddToSyncQueueMultiple(array $devices): array
    {
        $count = count($devices);
        $synced = 0;

        foreach ($devices as $device) {
            if (! $this->addToSyncQueue($device)) {
                continue;
            }

            ++$synced;
        }

        if ($synced > 0) {
            $this->em->flush();
        }

        return [$synced, $count - $synced];
    }

    private function handleCreateUpdate(
        ServiceDevice $serviceDevice,
        ServiceDevice $oldServiceDevice = null
    ) {
        if ($serviceDevice->getQosEnabled() !== BaseDevice::QOS_ANOTHER) {
            $serviceDevice->getQosDevices()->clear();
        }

        $this->qosSynchronizationManager->unsynchronizeDevice($serviceDevice, $oldServiceDevice);
    }

    private function addToSyncQueue(ServiceDevice $device): bool
    {
        $alreadyInQueue = $this->em->find(SearchServiceDeviceQueue::class, $device);
        if ($alreadyInQueue) {
            return false;
        }

        $searchDeviceQueue = new SearchServiceDeviceQueue();
        $searchDeviceQueue->setServiceDevice($device);
        $this->em->persist($searchDeviceQueue);

        return true;
    }

    public function getUnknownGridModel(): QueryBuilder
    {
        return $this->em->getRepository(ServiceDevice::class)
            ->createQueryBuilder('sd')
            ->join('sd.interface', 'i')
            ->join('i.device', 'd')
            ->where('sd.service IS NULL')
            ->andWhere('sd.firstSeen >= :seenFrom OR sd.lastSeen >= :seenFrom')
            ->setParameter('seenFrom', (new \DateTime())->modify('-1 week'), UtcDateTimeType::NAME);
    }

    public function getUnknownGridModelByDevice(Device $device): QueryBuilder
    {
        return $this->em->getRepository(ServiceDevice::class)->createQueryBuilder('sd')
            ->addSelect('i')
            ->join('sd.interface', 'i')
            ->where('sd.interface IN (:interfaces)')
            ->andWhere('sd.service IS NULL')
            ->andWhere('sd.firstSeen >= :seenFrom OR sd.lastSeen >= :seenFrom')
            ->groupBy('sd.id, i.id')
            ->setParameter('interfaces', $device->getNotDeletedInterfaces())
            ->setParameter('seenFrom', (new \DateTime())->modify('-1 week'), UtcDateTimeType::NAME);
    }

    public function getUnknownDevicesCount(): int
    {
        return $this->getUnknownGridModel()
            ->select('COUNT(sd.id) AS u_count')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
