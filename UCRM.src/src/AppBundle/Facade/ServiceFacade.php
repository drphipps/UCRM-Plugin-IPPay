<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Component\Geocoder\Geocoder;
use AppBundle\Component\Geocoder\Google\GoogleGeocodingException;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\InvoiceItemService;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceAccounting;
use AppBundle\Entity\ServiceAccountingCorrection;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Entity\Tariff;
use AppBundle\Event\Service\ServiceActivateEvent;
use AppBundle\Event\Service\ServiceAddEvent;
use AppBundle\Event\Service\ServiceArchiveEvent;
use AppBundle\Event\Service\ServiceEditEvent;
use AppBundle\Event\Service\ServiceEndEvent;
use AppBundle\Event\Service\ServiceSuspendEvent;
use AppBundle\Service\Options;
use AppBundle\Util\Arrays;
use AppBundle\Util\DateTimeFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use TransactionEventsBundle\TransactionDispatcher;

class ServiceFacade
{
    use QuoteServiceActionsTrait;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    /**
     * @var Geocoder
     */
    private $geocoder;

    public function __construct(
        EntityManager $em,
        Options $options,
        TransactionDispatcher $transactionDispatcher,
        Geocoder $geocoder
    ) {
        $this->em = $em;
        $this->options = $options;
        $this->transactionDispatcher = $transactionDispatcher;
        $this->geocoder = $geocoder;
    }

    public function createClonedService(Service $oldService): Service
    {
        $service = clone $oldService;
        $service->resetCollections();

        $today = new \DateTime('midnight');

        if ($oldService->getStatus() === Service::STATUS_ENDED) {
            $service->setActiveFrom(clone $today);
            $service->setActiveTo(null);
        } else {
            $service->setActiveFrom($oldService->getActiveFrom());
            $service->setActiveTo(
                $oldService->getActiveTo() && $oldService->getActiveTo() > $today
                    ? clone $oldService->getActiveTo()
                    : null
            );
        }

        $invoicingStart = Arrays::max(
            [
                clone $today,
                clone $service->getActiveFrom(),
                $service->getInvoicingLastPeriodEnd()
                    ? (clone $service->getInvoicingLastPeriodEnd())->modify('+1 day')
                    : null,
            ]
        );

        $service->setInvoicingStart($invoicingStart);
        $service->setStatus(Service::STATUS_ACTIVE);

        foreach ($oldService->getServiceSurcharges() as $serviceSurcharge) {
            $serviceSurcharge = clone $serviceSurcharge;
            $serviceSurcharge->setService($service);
            $service->addServiceSurcharge($serviceSurcharge);
        }

        // Reset OneToOne relation to prevent unique constraint error.
        $service->setSetupFee(null);
        $service->setEarlyTerminationFee(null);

        return $service;
    }

    public function handleCreate(Service $service): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($service) {
                $this->handleCreateUpdate($service);
                $this->em->persist($service);

                yield new ServiceAddEvent($service);
            }
        );
    }

    public function handleCreateWithSetupFee(
        Service $service,
        ?bool $blockPrepared,
        ?float $setupFeePrice
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($service, $blockPrepared, $setupFeePrice) {
                $this->handleCreateUpdate($service, $blockPrepared);
                $this->em->persist($service);
                $this->processSetupFeePrice($service, $setupFeePrice);

                yield new ServiceAddEvent($service);
            }
        );
    }

    public function handleUpdate(
        Service $service,
        Service $serviceBeforeUpdate
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($service, $serviceBeforeUpdate) {
                $this->handleCreateUpdate($service);
                $service->calculateStatus();

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    public function handleUpdateWithSetupFee(
        Service $service,
        Service $serviceBeforeUpdate,
        ?bool $blockPrepared,
        ?float $setupFeePrice
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($service, $serviceBeforeUpdate, $blockPrepared, $setupFeePrice) {
                $this->handleCreateUpdate($service, $blockPrepared);
                $service->calculateStatus();
                $this->processSetupFeePrice($service, $setupFeePrice);

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    public function handleEnd(Service $service, bool $allowEarlyTerminationFee): void
    {
        if ($service->getSupersededByService()) {
            throw new \InvalidArgumentException(
                'Ending the service is not allowed, because there is deferred change planned.'
            );
        }

        $this->transactionDispatcher->transactional(
            function () use ($service, $allowEarlyTerminationFee) {
                $oldService = clone $service;
                $service->setActiveTo(new \DateTime('-1 day'));

                if (! $allowEarlyTerminationFee) {
                    $service->setEarlyTerminationFeePrice(0);
                }

                yield new ServiceEditEvent($service, $oldService);
                yield new ServiceEndEvent($service);
            }
        );
    }

    public function handleSaveContract(
        Service $service,
        Service $serviceBeforeUpdate,
        ?float $setupFeePrice
    ): void {
        $this->transactionDispatcher->transactional(
            function () use ($service, $serviceBeforeUpdate, $setupFeePrice) {
                $this->processSetupFeePrice($service, $setupFeePrice);

                yield new ServiceEditEvent($service, $serviceBeforeUpdate);
            }
        );
    }

    private function processSetupFeePrice(Service $service, ?float $setupFeePrice): void
    {
        $fee = $service->getSetupFee();

        if ($fee && $fee->isInvoiced()) {
            return;
        }

        if ($setupFeePrice === null || round($setupFeePrice, 6) === 0.0) {
            $service->setSetupFee(null);

            return;
        }

        if (! $fee) {
            $fee = new Fee();
            $fee->setType(Fee::TYPE_SETUP_FEE);
            $fee->setClient($service->getClient());
            $fee->setService($service);
            $fee->setInvoiceLabel($this->options->get(Option::SETUP_FEE_INVOICE_LABEL));
            $fee->setName((string) $this->options->get(Option::SETUP_FEE_INVOICE_LABEL));
            $fee->setCreatedDate(new \DateTime());
            $fee->setTaxable($this->options->get(Option::SETUP_FEE_TAXABLE));
            $service->setSetupFee($fee);
        }

        $fee->setPrice($setupFeePrice);
    }

    public function handleDelete(Service $service, bool $keepServiceDevices): void
    {
        $this->transactionDispatcher->transactional(
            function (EntityManager $em) use ($service, $keepServiceDevices) {
                $activeToLimit = new \DateTime('-1 day midnight');
                if (! $service->getActiveTo() || $service->getActiveTo() > $activeToLimit) {
                    $service->setActiveTo($activeToLimit);
                }

                $service->setDeletedAt(new \DateTime());

                if (! $keepServiceDevices) {
                    foreach ($service->getServiceDevices() as $serviceDevice) {
                        $em->remove($serviceDevice);
                    }
                }

                yield new ServiceArchiveEvent($service);
            }
        );
    }

    public function handleObsolete(Service $service, Service $obsoleteService, ?bool $blockPrepared = null): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($service, $obsoleteService, $blockPrepared) {
                $oldService = clone $obsoleteService;
                $this->setObsolete($service, $obsoleteService, new \DateTimeImmutable());
                $this->handleCreateUpdate($service, $blockPrepared);
                $this->em->persist($service);

                yield new ServiceEditEvent($obsoleteService, $oldService);
                yield new ServiceAddEvent($service, $obsoleteService);
            }
        );
    }

    private function setObsolete(Service $newService, Service $serviceToSupersede, \DateTimeImmutable $date): void
    {
        // Set up old service as obsolete and deleted and associate the new one with it.
        $serviceToSupersede->setSupersededByService($newService);
        $newService->setStatus($serviceToSupersede->getStatus());
        $serviceToSupersede->setStatus(Service::STATUS_OBSOLETE);
        $newService->setDeletedAt(null);
        $serviceToSupersede->setDeletedAt(DateTimeFactory::createFromInterface($date));

        // Move service devices to new service.
        $serviceDevices = $serviceToSupersede->getServiceDevices();
        foreach ($serviceDevices as $serviceDevice) {
            $serviceDevice->setService($newService);
            $newService->addServiceDevice($serviceDevice);
            $serviceToSupersede->removeServiceDevice($serviceDevice);
        }

        // Move invoice items to new service
        $items = $this->em->getRepository(InvoiceItemService::class)->findBy(
            [
                'service' => $serviceToSupersede,
            ]
        );
        foreach ($items as $item) {
            $item->setOriginalService($item->getOriginalService() ?? $serviceToSupersede);
            $item->setService($newService);
        }

        // Move suspension to new service.
        $newService->setStopReason($serviceToSupersede->getStopReason());
        $serviceToSupersede->setStopReason(null);
        $newService->setSuspendedFrom($serviceToSupersede->getSuspendedFrom());
        $serviceToSupersede->setSuspendedFrom(null);
        $newService->setSuspendPostponedByClient($serviceToSupersede->getSuspendPostponedByClient());
        $serviceToSupersede->setSuspendPostponedByClient(false);

        // Move suspension periods to new service.
        $suspensionPeriods = $serviceToSupersede->getSuspensionPeriods();
        foreach ($suspensionPeriods as $suspensionPeriod) {
            $suspensionPeriod->setService($newService);
            $newService->addSuspensionPeriod($suspensionPeriod);
            $serviceToSupersede->removeSuspensionPeriod($suspensionPeriod);
        }

        // Move service accounting.
        $accountingResults = $this->em
            ->getRepository(ServiceAccounting::class)
            ->findBy(['service' => $serviceToSupersede]);
        foreach ($accountingResults as $result) {
            $result->setService($newService);
        }

        // Move service accounting correction data.
        $accountingCorrectionResults = $this->em
            ->getRepository(ServiceAccountingCorrection::class)
            ->findBy(['service' => $serviceToSupersede]);
        foreach ($accountingCorrectionResults as $result) {
            $result->setService($newService);
        }

        // Move payment plan to new service.
        if ($paymentPlans = $serviceToSupersede->getPaymentPlans()) {
            foreach ($paymentPlans as $paymentPlan) {
                $paymentPlan->setService($newService);
            }
            $serviceToSupersede->setPaymentPlans(new ArrayCollection());
            $newService->setPaymentPlans($paymentPlans);
        }
    }

    public function handleDefer(Service $serviceToDefer, Service $currentService, bool $isEditOfDeferredChange): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($serviceToDefer, $currentService, $isEditOfDeferredChange) {
                $oldService = clone $currentService;
                $this->handleCreateUpdate($serviceToDefer);

                $serviceToDefer->setDeletedAt(new \DateTime());
                $serviceToDefer->setStatus(Service::STATUS_DEFERRED);

                $currentService->setSupersededByService($serviceToDefer);

                // Do not override activeToBackup when editing already existing planned deferred change.
                // We need to keep the original backup in case the deferred change is cancelled.
                if (! $isEditOfDeferredChange) {
                    $currentService->setActiveToBackup(
                        $currentService->getActiveTo() ? clone $currentService->getActiveTo() : null
                    );
                }
                $currentService->setActiveTo((clone $serviceToDefer->getInvoicingStart())->modify('-1 day'));

                $this->em->persist($serviceToDefer);
                $this->em->flush();

                if ((clone $serviceToDefer->getInvoicingStart())->modify('midnight') <= new \DateTime('midnight')) {
                    $this->setObsolete($serviceToDefer, $currentService, new \DateTimeImmutable());
                }

                yield new ServiceEditEvent($currentService, $oldService);
                yield new ServiceAddEvent($serviceToDefer, $currentService);
            }
        );
    }

    public function cancelDeferredChange(Service $service): void
    {
        $this->em->transactional(
            function () use ($service) {
                $this->setCancelledDeferredChange($service);
            }
        );
    }

    public function handleActivateQuoted(Service $service): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($service) {
                $oldService = clone $service;
                if ($this->activateQuotedService($service)) {
                    yield new ServiceEditEvent($service, $oldService);
                    yield new ServiceActivateEvent($service, $oldService);
                }
            }
        );
    }

    private function setCancelledDeferredChange(Service $service): void
    {
        if (! $service->getSupersededByService()) {
            return;
        }
        $service->setActiveTo($service->getActiveToBackup() ? clone $service->getActiveToBackup() : null);
        $service->setActiveToBackup(null);

        $this->em->remove($service->getSupersededByService());
        $service->setSupersededByService(null);
    }

    public function applyDeferredChanges(\DateTimeImmutable $date): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($date) {
                $services = $this->em->getRepository(Service::class)->getServicesWithDeferredChanges($date);

                foreach ($services as $service) {
                    $oldService = clone $service;

                    $this->setObsolete($service->getSupersededByService(), $service, $date);

                    yield new ServiceEditEvent($service, $oldService);
                }
            }
        );
    }

    /**
     * @param Service[] $services
     */
    public function setSuspendDisabled(array $services): void
    {
        $this->transactionDispatcher->transactional(
            function () use ($services) {
                foreach ($services as $service) {
                    $serviceBeforeUpdate = clone $service;

                    $service->setStopReason(null);
                    $service->setSuspendedFrom(null);
                    $service->setSuspendPostponedByClient(false);

                    yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                    yield new ServiceSuspendEvent($service, $serviceBeforeUpdate);
                }
            }
        );
    }

    public function handleRemoveTaxWhenTariffSuperior(Tariff $tariff): void
    {
        if (! $tariff->getTaxable() || ! $tariff->getTax()) {
            return;
        }

        /** @var Service[] $services */
        $services = $this->em->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('s, t')
            ->join('s.tariff', 't')
            ->where('s.tariff = :tariff')
            ->andWhere('s.tax1 IS NOT NULL OR s.tax2 IS NOT NULL OR s.tax3 IS NOT NULL')
            ->setParameter('tariff', $tariff)
            ->getQuery()
            ->getResult();

        $this->transactionDispatcher->transactional(
            function () use ($services) {
                foreach ($services as $service) {
                    $serviceBeforeUpdate = clone $service;

                    $service->setTax1(null);
                    $service->setTax2(null);
                    $service->setTax3(null);

                    yield new ServiceEditEvent($service, $serviceBeforeUpdate);
                }
            }
        );
    }

    /**
     * @throws GoogleGeocodingException
     * @throws \RuntimeException
     */
    public function geocode(Service $service): void
    {
        if (! $location = $this->geocoder->geocodeAddress($service->getAddressForGeocoding())) {
            throw new \RuntimeException('Could not geocode service\'s address.');
        }

        $serviceBeforeUpdate = clone $service;
        $service->setAddressGpsLat($location->lat);
        $service->setAddressGpsLon($location->lon);

        $this->handleUpdate($service, $serviceBeforeUpdate);
    }

    private function handleCreateUpdate(Service $service, bool $blockPrepared = null)
    {
        if (! $service->getName() && $service->getTariff()) {
            $service->setName($service->getTariff()->getName());
        }

        if ($service->getInvoicingPeriodType() === Service::INVOICING_BACKWARDS) {
            $invoicingStart = clone $service->getInvoicingStart();
            $nextInvoicingDay = $invoicingStart->modify('first day of next month');
            $service->setNextInvoicingDay($nextInvoicingDay);
        } else {
            $service->setNextInvoicingDay($service->getInvoicingStart());
        }

        $today = new \DateTime('today midnight');
        if (null !== $blockPrepared && $service->getActiveFrom() > $today) {
            if ($blockPrepared && ! $service->getStopReason()) {
                // Service is "prepared" and we want to block it
                $reason = $this->em->find(ServiceStopReason::class, ServiceStopReason::STOP_REASON_PREPARED_ID);
                $service->setStopReason($reason);
                $service->setSuspendedFrom($today);
            } elseif (
                ! $blockPrepared
                && $service->getStopReason()
                && $service->getStopReason()->getId() === ServiceStopReason::STOP_REASON_PREPARED_ID
            ) {
                // Service is "prepared" and we want to unblock it
                $service->setStopReason(null);
                $service->setSuspendedFrom(null);
            }
        } elseif (
            $service->getActiveFrom() <= $today
            && $service->getStopReason()
            && $service->getStopReason()->getId() === ServiceStopReason::STOP_REASON_PREPARED_ID
        ) {
            // Service is no longer "prepared" and we want to unblock it
            $service->setStopReason(null);
            $service->setSuspendedFrom(null);
        }
    }
}
