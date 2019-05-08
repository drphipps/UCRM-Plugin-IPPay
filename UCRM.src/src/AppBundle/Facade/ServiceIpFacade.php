<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\QoS\QoSSynchronizationManager;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use AppBundle\Event\ServiceIp\ServiceIpAddEvent;
use AppBundle\Event\ServiceIp\ServiceIpDeleteEvent;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceIpFacade
{
    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var QoSSynchronizationManager
     */
    private $qosSynchronizationManager;

    public function __construct(
        TransactionDispatcher $transactionDispatcher,
        QoSSynchronizationManager $qosSynchronizationManager
    ) {
        $this->transactionDispatcher = $transactionDispatcher;
        $this->qosSynchronizationManager = $qosSynchronizationManager;
    }

    public function setDefaults(ServiceDevice $serviceDevice, ServiceIp $serviceIp): void
    {
        $serviceIp->setServiceDevice($serviceDevice);
        $serviceDevice->addServiceIp($serviceIp);
    }

    public function handleCreate(ServiceIp $serviceIp): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($serviceIp) {
                $this->qosSynchronizationManager->unsynchronizeServiceIp($serviceIp);
                $entityManager->persist($serviceIp);
                yield new ServiceIpAddEvent($serviceIp);
            }
        );
    }

    public function handleDelete(ServiceIp $serviceIp): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($serviceIp) {
                $entityManager->remove($serviceIp);
                yield new ServiceIpDeleteEvent($serviceIp);
            }
        );
    }
}
