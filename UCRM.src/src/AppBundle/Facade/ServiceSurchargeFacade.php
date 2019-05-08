<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeAddEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeDeleteEvent;
use AppBundle\Event\ServiceSurcharge\ServiceSurchargeEditEvent;
use Doctrine\ORM\EntityManagerInterface;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceSurchargeFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function setServiceSurchargeDefaults(Service $service, ServiceSurcharge $serviceSurcharge): void
    {
        $serviceSurcharge->setService($service);
    }

    public function handleCreate(ServiceSurcharge $serviceSurcharge): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($serviceSurcharge) {
                $this->entityManager->persist($serviceSurcharge);

                yield new ServiceSurchargeAddEvent($serviceSurcharge);
            }
        );
    }

    public function handleUpdate(ServiceSurcharge $serviceSurcharge, ServiceSurcharge $serviceSurchargeBeforeUpdate): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($serviceSurcharge, $serviceSurchargeBeforeUpdate) {
                yield new ServiceSurchargeEditEvent($serviceSurcharge, $serviceSurchargeBeforeUpdate);
            }
        );
    }

    public function handleDelete(ServiceSurcharge $serviceSurcharge): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($serviceSurcharge) {
                $this->entityManager->remove($serviceSurcharge);

                yield new ServiceSurchargeDeleteEvent($serviceSurcharge);
            }
        );
    }
}
