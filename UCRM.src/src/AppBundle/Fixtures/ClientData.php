<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\Fixtures;

use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\Device;
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Entity\ServiceSurcharge;
use AppBundle\Entity\State;
use AppBundle\Entity\SuspensionPeriod;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Factory\Financial\FinancialItemServiceFactory;
use AppBundle\Service\Financial\FinancialTotalCalculator;
use AppBundle\Service\Invoice\InvoiceBuilderFactory;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClientData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const CLIENT_WITH_ENDED_SERVICE = '1';
    public const CLIENT_WITH_SEPARATELY_INVOICED_SERVICE = '2';
    public const CLIENT_WITH_MULTIPLE_SERVICES = '3';
    public const CLIENT_WITH_ONLY_FEE = '4';
    public const CLIENT_WITH_SERVICE_CONNECTED_TO_DEVICE = '5';
    public const CLIENT_WITH_SERVICES_AFTER_DEFERRED_CHANGE = '6';
    public const CLIENT_WITH_INVOICES_TO_LATE_FEE = '7';
    public const CLIENT_WITH_SUSPENDED_SERVICE = '8';
    public const CLIENT_WITH_OPEN_QUOTE_FOR_SERVICE = '9';
    public const CLIENT_LEAD = '10';

    public function load(ObjectManager $em)
    {
        assert($em instanceof EntityManager);
        $this->loadClientWithEndedService($em);
        $this->loadClientWithSeparatelyInvoicedService($em);
        $this->loadClientWithMultipleServices($em);
        $this->loadClientWithInvoicesToLateFee($em);
        $this->loadClientWithOnlyFee($em);
        $this->loadClientWithServiceConnectedToDevice($em);
        $this->loadClientWithServicesAfterDeferredChange($em);
        $this->loadClientWithSuspendedService($em);
        $this->loadClientWithOpenQuoteForService($em);
        $this->loadLead($em);

        $em->flush();
    }

    public function getOrder()
    {
        return 20;
    }

    private function loadClientWithEndedService(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_ENDED_SERVICE);
        $client->getUser()->setLastName('Client with ended service');
        $em->persist($client);

        $service = $this->createService($client);
        $service->setName('Ended service');
        $service->setActiveTo(new \DateTime());
        $em->persist($service);

        $factory = $this->container->get(InvoiceBuilderFactory::class);
        $builder = $factory->create($client);
        $builder->addService($service);
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::PAID);
        $em->persist($invoice);
    }

    private function loadClientWithSeparatelyInvoicedService(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_SEPARATELY_INVOICED_SERVICE);
        $client->getUser()->setLastName('Client with separately invoiced service');
        $em->persist($client);

        $service = $this->createService($client);
        $service->setName('Separately invoiced service');
        $service->setInvoicingSeparately(true);
        $em->persist($service);

        $service = $this->createService($client);
        $service->setName('Normal service');
        $em->persist($service);

        $fee = new Fee();
        $fee->setType(Fee::TYPE_LATE_FEE);
        $fee->setClient($client);
        $fee->setName('Fee to be invoiced');
        $fee->setCreatedDate(new \DateTime('-1 month'));
        $fee->setInvoiced(false);
        $fee->setPrice(10);
        $fee->setTaxable(false);
        $em->persist($fee);
    }

    private function loadClientWithMultipleServices(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_MULTIPLE_SERVICES);
        $client->getUser()->setLastName('Client with multiple services');
        $em->persist($client);

        $service1 = $this->createService($client);
        $service1->setName('Service with surcharge');
        $em->persist($service1);

        $surcharge = new ServiceSurcharge();
        $surcharge->setService($service1);
        $surcharge->setSurcharge($this->getReference(SurchargeData::REFERENCE_SURCHARGE_WIFI));
        $surcharge->setPrice($surcharge->getSurcharge()->getPrice());
        $surcharge->setInvoiceLabel($surcharge->getSurcharge()->getInvoiceLabelForView());
        $service1->addServiceSurcharge($surcharge);
        $em->persist($surcharge);

        $service2 = $this->createService($client);
        $service2->setName('Service 2');
        $service2->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MIDDLE));
        $service2->setTariffPeriod($service2->getTariff()->getPeriodByPeriod(1));
        $service2->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $em->persist($service2);

        $service3 = $this->createService($client);
        $service3->setName('Service 3');
        $service3->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MAXI));
        $service3->setTariffPeriod($service3->getTariff()->getPeriodByPeriod(1));
        $service3->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $em->persist($service3);

        $service4 = $this->createService($client);
        $service4->setName('Service with surcharge and separate invoicing');
        $service4->setInvoicingSeparately(true);
        $em->persist($service4);

        $surcharge = new ServiceSurcharge();
        $surcharge->setService($service4);
        $surcharge->setSurcharge($this->getReference(SurchargeData::REFERENCE_SURCHARGE_WIFI));
        $surcharge->setPrice($surcharge->getSurcharge()->getPrice());
        $surcharge->setInvoiceLabel($surcharge->getSurcharge()->getInvoiceLabelForView());
        $service4->addServiceSurcharge($surcharge);
        $em->persist($surcharge);

        $service5 = $this->createService($client);
        $service5->setName('Service 2 with separate invoicing');
        $service5->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MIDDLE));
        $service5->setTariffPeriod($service5->getTariff()->getPeriodByPeriod(1));
        $service5->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $service5->setInvoicingSeparately(true);
        $em->persist($service5);

        $service6 = $this->createService($client);
        $service6->setName('Service 3 with separate invoicing');
        $service6->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MAXI));
        $service6->setTariffPeriod($service6->getTariff()->getPeriodByPeriod(1));
        $service6->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $service6->setInvoicingSeparately(true);
        $em->persist($service6);
    }

    private function loadClientWithInvoicesToLateFee(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_INVOICES_TO_LATE_FEE);
        $client->getUser()->setLastName('Client with invoices to late fee');
        $em->persist($client);

        $service1 = $this->createService($client);
        $service1->setName('Service with surcharge');
        $em->persist($service1);

        $surcharge = new ServiceSurcharge();
        $surcharge->setService($service1);
        $surcharge->setSurcharge($this->getReference(SurchargeData::REFERENCE_SURCHARGE_WIFI));
        $surcharge->setPrice($surcharge->getSurcharge()->getPrice());
        $surcharge->setInvoiceLabel($surcharge->getSurcharge()->getInvoiceLabelForView());
        $service1->addServiceSurcharge($surcharge);
        $em->persist($surcharge);

        $service2 = $this->createService($client);
        $service2->setName('Service 2');
        $service2->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MIDDLE));
        $service2->setTariffPeriod($service2->getTariff()->getPeriodByPeriod(1));
        $service2->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $em->persist($service2);

        $service3 = $this->createService($client);
        $service3->setName('Service 3');
        $service3->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MAXI));
        $service3->setTariffPeriod($service3->getTariff()->getPeriodByPeriod(1));
        $service3->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $em->persist($service3);

        $service4 = $this->createService($client);
        $service4->setName('Service with surcharge and separate invoicing');
        $service4->setInvoicingSeparately(true);
        $em->persist($service4);

        $surcharge = new ServiceSurcharge();
        $surcharge->setService($service4);
        $surcharge->setSurcharge($this->getReference(SurchargeData::REFERENCE_SURCHARGE_WIFI));
        $surcharge->setPrice($surcharge->getSurcharge()->getPrice());
        $surcharge->setInvoiceLabel($surcharge->getSurcharge()->getInvoiceLabelForView());
        $service4->addServiceSurcharge($surcharge);
        $em->persist($surcharge);

        $service5 = $this->createService($client);
        $service5->setName('Service 2 with separate invoicing');
        $service5->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MIDDLE));
        $service5->setTariffPeriod($service5->getTariff()->getPeriodByPeriod(1));
        $service5->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $service5->setInvoicingSeparately(true);
        $em->persist($service5);

        $service6 = $this->createService($client);
        $service6->setName('Service 3 with separate invoicing');
        $service6->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MAXI));
        $service6->setTariffPeriod($service6->getTariff()->getPeriodByPeriod(1));
        $service6->setInvoicingStart(new \DateTime('-1 year + 14 days'));
        $service6->setInvoicingSeparately(true);
        $em->persist($service6);

        $factory = $this->container->get(InvoiceBuilderFactory::class);

        $builder = $factory->create($client);
        $builder->addService($service1);
        $builder->addService($service2);
        $builder->addService($service3);
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $invoice->setDueDate(new \DateTime('-1 year'));
        $em->persist($invoice);

        $builder = $factory->create($client);
        $builder->addService($service4);
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $invoice->setDueDate(new \DateTime('-1 year'));
        $em->persist($invoice);

        $builder = $factory->create($client);
        $builder->addService($service5);
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $invoice->setDueDate(new \DateTime('-1 year'));
        $em->persist($invoice);

        $builder = $factory->create($client);
        $builder->addService($service6);
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $invoice->setDueDate(new \DateTime('-1 year'));
        $em->persist($invoice);
    }

    private function loadClientWithOnlyFee(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_ONLY_FEE);
        $client->getUser()->setLastName('Client with only fee');
        $em->persist($client);

        $fee = new Fee();
        $fee->setType(Fee::TYPE_LATE_FEE);
        $fee->setClient($client);
        $fee->setName('Fee to be invoiced');
        $fee->setCreatedDate(new \DateTime('-1 month'));
        $fee->setInvoiced(false);
        $fee->setPrice(10);
        $fee->setTaxable(true);
        $em->persist($fee);
    }

    private function loadClientWithServiceConnectedToDevice(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_SERVICE_CONNECTED_TO_DEVICE);
        $client->getUser()->setLastName('Client with service connected to device');
        $em->persist($client);

        $service = $this->createService($client);
        $service->setName('Service with device');
        $service->setActiveTo(new \DateTime('+1 month'));
        $em->persist($service);

        /** @var Device $device */
        $device = $this->getReference(NetworkData::REFERENCE_DEVICE_EDGE_OS);

        $serviceDevice = new ServiceDevice();
        $serviceDevice->setService($service);
        $serviceDevice->setInterface($device->getNotDeletedInterfaces()->first());
        $service->addServiceDevice($serviceDevice);
        $em->persist($serviceDevice);
    }

    private function loadClientWithServicesAfterDeferredChange(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_SERVICES_AFTER_DEFERRED_CHANGE);
        $client->getUser()->setLastName('Client with services after deferred change');
        $em->persist($client);

        $obsoleteService = $this->createService($client);
        $obsoleteService->setName('Service with deferred service change');
        $obsoleteService->setDeletedAt(new \DateTime());
        $obsoleteService->setActiveTo(new \DateTime());
        $obsoleteService->setNextInvoicingDay(new \DateTime());
        $obsoleteService->setStatus(Service::STATUS_OBSOLETE);
        $em->persist($obsoleteService);

        $service = $this->createService($client);
        $service->setName('Deferred service');
        $service->setInvoicingStart(new \DateTime());
        $obsoleteService->setSupersededByService($service);
        $em->persist($service);

        $obsoleteService = $this->createService($client);
        $obsoleteService->setName('Service with deferred service change and separate invoicing');
        $obsoleteService->setDeletedAt(new \DateTime());
        $obsoleteService->setActiveTo(new \DateTime());
        $obsoleteService->setNextInvoicingDay(new \DateTime());
        $obsoleteService->setStatus(Service::STATUS_OBSOLETE);
        $obsoleteService->setInvoicingSeparately(true);
        $em->persist($obsoleteService);

        $service = $this->createService($client);
        $service->setName('Deferred service with separate invoicing');
        $service->setInvoicingStart(new \DateTime());
        $service->setInvoicingSeparately(true);
        $obsoleteService->setSupersededByService($service);
        $em->persist($service);
    }

    private function loadClientWithSuspendedService(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_SUSPENDED_SERVICE);
        $client->getUser()->setLastName('Client with suspended service');
        $client->setHasSuspendedService(true);
        $client->setHasOverdueInvoice(true);
        $em->persist($client);

        /** @var Device $device */
        $device = $this->getReference(NetworkData::REFERENCE_DEVICE_EDGE_OS);
        $factory = $this->container->get(InvoiceBuilderFactory::class);
        $builder = $factory->create($client);

        // Suspended service without separate invoicing
        $service = $this->createService($client);
        $service->setName('Suspended service');
        $service->setStopReason($em->find(ServiceStopReason::class, ServiceStopReason::STOP_REASON_OVERDUE_ID));
        $service->setInvoicingStart(new \DateTime('-2 months'));
        $em->persist($service);
        $builder->addService($service);

        $suspensionPeriod = new SuspensionPeriod();
        $suspensionPeriod->setSince(new \DateTime('-2 months'));
        $suspensionPeriod->setService($service);
        $service->addSuspensionPeriod($suspensionPeriod);

        $serviceDevice = new ServiceDevice();
        $serviceDevice->setService($service);
        $serviceDevice->setInterface($device->getNotDeletedInterfaces()->first());
        $service->addServiceDevice($serviceDevice);
        $em->persist($serviceDevice);

        // Suspended service with separate invoicing
        $service = $this->createService($client);
        $service->setName('Suspended service with separate invoicing');
        $service->setStopReason($em->find(ServiceStopReason::class, ServiceStopReason::STOP_REASON_OVERDUE_ID));
        $service->setInvoicingStart(new \DateTime('-2 months'));
        $service->setInvoicingSeparately(true);
        $em->persist($service);
        $builder->addService($service);

        $suspensionPeriod = new SuspensionPeriod();
        $suspensionPeriod->setSince(new \DateTime('-2 months'));
        $suspensionPeriod->setService($service);
        $service->addSuspensionPeriod($suspensionPeriod);

        $serviceDevice = new ServiceDevice();
        $serviceDevice->setService($service);
        $serviceDevice->setInterface($device->getNotDeletedInterfaces()->first());
        $service->addServiceDevice($serviceDevice);
        $em->persist($serviceDevice);

        // Invoice
        $invoice = $builder->getInvoice();
        $invoice->setInvoiceStatus(Invoice::UNPAID);
        $invoice->setDueDate(new \DateTime('-1 year'));
        $em->persist($invoice);
    }

    private function loadClientWithOpenQuoteForService(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_WITH_OPEN_QUOTE_FOR_SERVICE);
        $client->getUser()->setLastName('Client with quoted service');
        $client->setHasSuspendedService(false);
        $client->setHasOverdueInvoice(false);
        $em->persist($client);

        // Quoted service
        $service = $this->createService($client);
        $service->setName('Quoted service');
        $service->setStatus(Service::STATUS_QUOTED);
        $em->persist($service);

        // Quote
        $quote = $this->createQuoteForService($client, $service);
        $em->persist($quote);
    }

    private function loadLead(EntityManager $em): void
    {
        $client = $this->createClient($em);
        $client->setUserIdent(self::CLIENT_LEAD);
        $client->getUser()->setLastName('Lead');
        $client->setIsLead(true);
        $em->persist($client);

        // Quoted service
        $service = $this->createService($client);
        $service->setName('Quoted service');
        $service->setStatus(Service::STATUS_QUOTED);
        $em->persist($service);

        // Quote
        $quote = $this->createQuoteForService($client, $service);
        $em->persist($quote);
    }

    private function createClient(EntityManager $em): Client
    {
        $client = new Client();
        $client->setCountry($em->getReference(Country::class, 249));
        $client->setState($em->getReference(State::class, 5));
        $client->setOrganization($this->getReference(OrganizationData::REFERENCE_DEFAULT_ORGANIZATION));
        $client->setClientType(Client::TYPE_RESIDENTIAL);
        $client->setStreet1('2580 Orchard Parkway');
        $client->setCity('San Jose');
        $client->setZipCode('95131');
        $client->setRegistrationDate(new \DateTime('-1 year'));
        $client->setTax1($this->getReference(TaxData::REFERENCE_DEFAULT_TAX));
        $client->setTax2($this->getReference(TaxData::REFERENCE_NON_DEFAULT_TAX));

        return $client;
    }

    private function createService(Client $client): Service
    {
        $service = new Service();
        $service->setClient($client);
        $service->setTariff($this->getReference(OrganizationData::REFERENCE_TARIFF_MINI));
        $service->setTariffPeriod($service->getTariff()->getPeriodByPeriod(1));
        $service->setInvoicingStart(new \DateTime('-1 year'));
        $service->setInvoicingPeriodType(Service::INVOICING_BACKWARDS);
        $service->setContractLengthType(Service::CONTRACT_OPEN);
        $service->setActiveFrom(new \DateTime('-1 year'));
        $service->setInvoicingProratedSeparately(false);
        $service->setTax1($this->getReference(TaxData::REFERENCE_NON_DEFAULT_TAX));
        $service->setTax2($this->getReference(TaxData::REFERENCE_DEFAULT_TAX));

        return $service;
    }

    private function createQuoteForService(Client $client, Service $service): Quote
    {
        $quote = $this->container->get(FinancialFactory::class)->createQuote($client, new \DateTimeImmutable());
        $item = $this->container->get(FinancialItemServiceFactory::class)->createDefaultQuoteItem($service);
        $item->setQuote($quote);
        $quote->getQuoteItems()->add($item);
        $this->container->get(FinancialTotalCalculator::class)->computeTotal($quote);

        return $quote;
    }
}
