<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use ApiBundle\Mapper\ClientLogMapper;
use ApiBundle\Mapper\ClientMapper;
use ApiBundle\Mapper\InvoiceMapper;
use ApiBundle\Mapper\PaymentMapper;
use ApiBundle\Mapper\PaymentPlanMapper;
use ApiBundle\Mapper\QuoteMapper;
use ApiBundle\Mapper\RefundMapper;
use ApiBundle\Mapper\ServiceMapper;
use ApiBundle\Request\InvoiceCollectionRequest;
use ApiBundle\Request\PaymentCollectionRequest;
use ApiBundle\Request\PaymentPlanCollectionRequest;
use ApiBundle\Request\RefundCollectionRequest;
use ApiBundle\Request\ServiceCollectionRequest;
use AppBundle\Entity\Client;
use AppBundle\Request\QuoteCollectionRequest;
use JMS\Serializer\SerializerInterface;
use SchedulingBundle\Api\Map\PublicJobMap;
use SchedulingBundle\Api\Mapper\JobMapper;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Request\JobCollectionRequest;
use TicketingBundle\Api\Mapper\TicketMapper;
use TicketingBundle\Api\Request\TicketCollectionRequest;
use TicketingBundle\DataProvider\TicketDataProvider;

class ClientExportDataProvider
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentDataProvider
     */
    private $documentDataProvider;

    /**
     * @var InvoiceDataProvider
     */
    private $invoiceDataProvider;

    /**
     * @var QuoteDataProvider
     */
    private $quoteDataProvider;

    /**
     * @var ServiceDataProvider
     */
    private $serviceDataProvider;

    /**
     * @var PaymentDataProvider
     */
    private $paymentDataProvider;

    /**
     * @var RefundDataProvider
     */
    private $refundDataProvider;

    /**
     * @var PaymentPlanDataProvider
     */
    private $paymentPlanDataProvider;

    /**
     * @var TicketDataProvider
     */
    private $ticketDataProvider;

    /**
     * @var JobDataProvider
     */
    private $jobDataProvider;

    /**
     * @var ClientMapper
     */
    private $clientMapper;

    /**
     * @var ServiceMapper
     */
    private $serviceMapper;

    /**
     * @var InvoiceMapper
     */
    private $invoiceMapper;

    /**
     * @var QuoteMapper
     */
    private $quoteMapper;

    /**
     * @var PaymentMapper
     */
    private $paymentMapper;

    /**
     * @var PaymentPlanMapper
     */
    private $paymentPlanMapper;

    /**
     * @var RefundMapper
     */
    private $refundMapper;

    /**
     * @var ClientLogMapper
     */
    private $clientLogMapper;

    /**
     * @var TicketMapper
     */
    private $ticketMapper;

    /**
     * @var JobMapper
     */
    private $jobMapper;

    public function __construct(
        SerializerInterface $serializer,
        DocumentDataProvider $documentDataProvider,
        InvoiceDataProvider $invoiceDataProvider,
        QuoteDataProvider $quoteDataProvider,
        ServiceDataProvider $serviceDataProvider,
        PaymentDataProvider $paymentDataProvider,
        RefundDataProvider $refundDataProvider,
        PaymentPlanDataProvider $paymentPlanDataProvider,
        TicketDataProvider $ticketDataProvider,
        JobDataProvider $jobDataProvider,
        ClientMapper $clientMapper,
        ServiceMapper $serviceMapper,
        InvoiceMapper $invoiceMapper,
        QuoteMapper $quoteMapper,
        PaymentMapper $paymentMapper,
        PaymentPlanMapper $paymentPlanMapper,
        RefundMapper $refundMapper,
        ClientLogMapper $clientLogMapper,
        TicketMapper $ticketMapper,
        JobMapper $jobMapper
    ) {
        $this->serializer = $serializer;
        $this->documentDataProvider = $documentDataProvider;
        $this->invoiceDataProvider = $invoiceDataProvider;
        $this->quoteDataProvider = $quoteDataProvider;
        $this->serviceDataProvider = $serviceDataProvider;
        $this->paymentDataProvider = $paymentDataProvider;
        $this->refundDataProvider = $refundDataProvider;
        $this->paymentPlanDataProvider = $paymentPlanDataProvider;
        $this->ticketDataProvider = $ticketDataProvider;
        $this->jobDataProvider = $jobDataProvider;
        $this->clientMapper = $clientMapper;
        $this->serviceMapper = $serviceMapper;
        $this->invoiceMapper = $invoiceMapper;
        $this->quoteMapper = $quoteMapper;
        $this->paymentMapper = $paymentMapper;
        $this->paymentPlanMapper = $paymentPlanMapper;
        $this->refundMapper = $refundMapper;
        $this->clientLogMapper = $clientLogMapper;
        $this->ticketMapper = $ticketMapper;
        $this->jobMapper = $jobMapper;
    }

    public function getClient(Client $client): string
    {
        return $this->serializer->serialize(
            $this->clientMapper->reflect($client),
            'json'
        );
    }

    public function getServices(Client $client): string
    {
        $request = new ServiceCollectionRequest();
        $request->clientId = $client->getId();

        return $this->serializer->serialize(
            $this->serviceMapper->reflectCollection(
                $this->serviceDataProvider->getCollection($request)
            ),
            'json'
        );
    }

    public function getPayments(Client $client): string
    {
        $request = new PaymentCollectionRequest();
        $request->clientId = $client->getId();

        return $this->serializer->serialize(
            $this->paymentMapper->reflectCollection(
                $this->paymentDataProvider->getCollection($request)
            ),
            'json'
        );
    }

    public function getRefunds(Client $client): string
    {
        $request = new RefundCollectionRequest();
        $request->clientId = $client->getId();

        return $this->serializer->serialize(
            $this->refundMapper->reflectCollection(
                $this->refundDataProvider->getCollection($request)
            ),
            'json'
        );
    }

    public function getPaymentPlans(Client $client): string
    {
        $request = new PaymentPlanCollectionRequest();
        $request->clientId = $client->getId();

        return $this->serializer->serialize(
            $this->paymentPlanMapper->reflectCollection(
                $this->paymentPlanDataProvider->getCollection($request)
            ),
            'json'
        );
    }

    public function getTickets(Client $client): string
    {
        $request = new TicketCollectionRequest();
        $request->client = $client;
        $request->order = 'id';
        $request->public = true;

        return $this->serializer->serialize(
            $this->ticketMapper->reflectCollection(
                $this->ticketDataProvider->getTicketsAPI($request),
                [
                    'publicActivity' => true,
                ]
            ),
            'json'
        );
    }

    public function getJobs(Client $client): string
    {
        $request = new JobCollectionRequest();
        $request->client = $client;
        $request->public = true;

        return $this->serializer->serialize(
            $this->jobMapper->reflectCollection(
                $this->jobDataProvider->getAllJobs($request),
                [],
                PublicJobMap::class
            ),
            'json'
        );
    }

    public function getInvoices(Client $client): string
    {
        $request = new InvoiceCollectionRequest();
        $request->clientId = $client->getId();

        return $this->serializer->serialize(
            $this->invoiceMapper->reflectCollection(
                $this->invoiceDataProvider->getInvoiceCollection($request)
            ),
            'json'
        );
    }

    public function getQuotes(Client $client): string
    {
        $request = new QuoteCollectionRequest();
        $request->client = $client;

        return $this->serializer->serialize(
            $this->quoteMapper->reflectCollection(
                $this->quoteDataProvider->getQuotes($request)
            ),
            'json'
        );
    }
}
