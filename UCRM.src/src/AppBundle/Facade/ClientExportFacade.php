<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Export\ExportPathData;
use AppBundle\DataProvider\ClientExportDataProvider;
use AppBundle\DataProvider\DocumentDataProvider;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\DataProvider\QuoteDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Util\Strings;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TicketingBundle\DataProvider\TicketDataProvider;
use ZipStream\ZipStream;

class ClientExportFacade
{
    /**
     * @var ClientExportDataProvider
     */
    private $exportDataProvider;

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
     * @var TicketDataProvider
     */
    private $ticketDataProvider;

    public function __construct(
        ClientExportDataProvider $exportDataProvider,
        DocumentDataProvider $documentDataProvider,
        InvoiceDataProvider $invoiceDataProvider,
        QuoteDataProvider $quoteDataProvider,
        TicketDataProvider $ticketDataProvider
    ) {
        $this->exportDataProvider = $exportDataProvider;
        $this->documentDataProvider = $documentDataProvider;
        $this->invoiceDataProvider = $invoiceDataProvider;
        $this->quoteDataProvider = $quoteDataProvider;
        $this->ticketDataProvider = $ticketDataProvider;
    }

    public function handleClientExport(Client $client): StreamedResponse
    {
        $response = new StreamedResponse(
            function () use ($client) {
                $zipStream = new ZipStream(
                    sprintf(
                        'client-export-%d-%s.zip',
                        $client->getId(),
                        date('YmdHis')
                    )
                );

                $this->addClient($client, $zipStream);
                $this->addServices($client, $zipStream);
                $this->addPayments($client, $zipStream);
                $this->addRefunds($client, $zipStream);
                $this->addPaymentPlans($client, $zipStream);
                $this->addTickets($client, $zipStream);
                $this->addJobs($client, $zipStream);
                $this->addDocuments($client, $zipStream);
                $this->addInvoices($client, $zipStream);
                $this->addQuotes($client, $zipStream);

                $zipStream->finish();
            }
        );

        return $response;
    }

    private function addClient(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'client.json',
            $this->exportDataProvider->getClient($client)
        );
    }

    private function addServices(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'services.json',
            $this->exportDataProvider->getServices($client)
        );
    }

    private function addPayments(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'payments.json',
            $this->exportDataProvider->getPayments($client)
        );
    }

    private function addRefunds(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'refunds.json',
            $this->exportDataProvider->getRefunds($client)
        );
    }

    private function addPaymentPlans(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'payment_plans.json',
            $this->exportDataProvider->getPaymentPlans($client)
        );
    }

    private function addTickets(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'tickets.json',
            $this->exportDataProvider->getTickets($client)
        );

        $attachmentPaths = $this->ticketDataProvider->getAllPublicAttachmentPathsForClient($client);
        foreach ($attachmentPaths as $ticketId => $paths) {
            if (! $paths) {
                continue;
            }

            $this->addFromPaths(
                $zipStream,
                sprintf('tickets/%d', $ticketId),
                $paths
            );
        }
    }

    private function addJobs(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'jobs.json',
            $this->exportDataProvider->getJobs($client)
        );
    }

    private function addDocuments(Client $client, ZipStream $zipStream): void
    {
        $this->addFromPaths(
            $zipStream,
            'documents',
            $this->documentDataProvider->getAllPathsForClient($client)
        );
    }

    private function addInvoices(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'invoices.json',
            $this->exportDataProvider->getInvoices($client)
        );

        $this->addFromPaths(
            $zipStream,
            'invoices',
            $this->invoiceDataProvider->getAllInvoicePdfPathsForClient($client)
        );
    }

    private function addQuotes(Client $client, ZipStream $zipStream): void
    {
        $zipStream->addFile(
            'quotes.json',
            $this->exportDataProvider->getQuotes($client)
        );

        $this->addFromPaths(
            $zipStream,
            'quotes',
            $this->quoteDataProvider->getAllQuotePdfPathsForClient($client)
        );
    }

    /**
     * @param ExportPathData[] $paths
     */
    private function addFromPaths(ZipStream $zipStream, string $folder, array $paths): void
    {
        $names = [];
        foreach ($paths as $path) {
            $name = Strings::sanitizeFileName($path->getName());
            while (in_array($name, $names, true)) {
                $name = sprintf('%s_%s', uniqid(), Strings::sanitizeFileName($path->getName()));
            }
            $names[] = $name;
            $zipStream->addFileFromPath(
                sprintf('%s/%s', $folder, $name),
                $path->getPath()
            );
        }
    }
}
