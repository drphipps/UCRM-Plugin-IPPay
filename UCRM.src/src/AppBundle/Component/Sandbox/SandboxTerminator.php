<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Sandbox;

use AppBundle\Component\Command\Ports\PortsBump;
use AppBundle\Component\Command\UAS\UasBump;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Refund;
use AppBundle\Entity\Service;
use AppBundle\Exception\PaymentPlanException;
use AppBundle\Exception\ResetException;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Facade\OptionsFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Facade\PaymentPlanFacade;
use AppBundle\Facade\QuoteFacade;
use AppBundle\Facade\RefundFacade;
use AppBundle\Facade\ServiceFacade;
use AppBundle\FileManager\MaintenanceFileManager;
use AppBundle\Form\Data\SandboxTerminationData;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceOutageUpdater;
use AppBundle\Service\ServiceStatusUpdater;
use AppBundle\Util\Invoicing;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RabbitMqBundle\QueueCleaner;
use RabbitMqBundle\RabbitMqEnqueuer;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Service\Facade\JobFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Service\Facade\TicketFacade;

class SandboxTerminator
{
    private const RABBIT_QUEUES = [
        'delete_invoices',
        'export_invoices',
        'export_quotes',
        'export_clients',
        'export_client_logs_view',
        'export_invoice_overview',
        'export_quote_overview',
        'export_job',
        'export_payment_overview',
        'export_payment_receipt',
        'approve_draft',
        'generate_drafts',
        'initialize_draft_generation',
        'send_invoice',
        'fcc_report',
        'fcc_block_id',
        'synchronize_job_to_google_calendar',
        'send_email',
        'report_data_usage_generate',
        'backup_sync_request',
        'payment_plan_cancel',
        'payment_plan_update_amount',
        'payment_plan_unlink',
        'webhook_event_request',
        'client_import',
        'payment_import',
        'send_invitation_emails',
    ];

    private const RABBIT_QUEUES_SMART = [
        'delete_invoices',
        'export_invoices',
        'export_quotes',
        'export_clients',
        'export_client_logs_view',
        'export_invoice_overview',
        'export_quote_overview',
        'export_job',
        'export_payment_overview',
        'export_payment_receipt',
        'approve_draft',
        'generate_drafts',
        'initialize_draft_generation',
        'send_invoice',
        'fcc_report',
        'fcc_block_id',
        'synchronize_job_to_google_calendar',
        'report_data_usage_generate',
        'backup_sync_request',
        'payment_plan_cancel',
        'payment_plan_update_amount',
        'payment_plan_unlink',
        'webhook_event_request',
        'client_import',
        'payment_import',
        'send_invitation_emails',
    ];

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var QueueCleaner
     */
    private $rabbitQueueCleaner;

    /**
     * @var ServiceStatusUpdater
     */
    private $serviceStatusUpdater;

    /**
     * @var ClientStatusUpdater
     */
    private $clientStatusUpdater;

    /**
     * @var ServiceOutageUpdater
     */
    private $serviceOutageUpdater;

    /**
     * @var OptionsFacade
     */
    private $optionsFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PortsBump
     */
    private $portsBump;

    /**
     * @var UasBump
     */
    private $uasBump;

    /**
     * @var string
     */
    private $factoryResetFile;

    /**
     * @var MaintenanceFileManager
     */
    private $maintenanceFileManager;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var RefundFacade
     */
    private $refundFacade;

    /**
     * @var ServiceFacade
     */
    private $serviceFacade;

    /**
     * @var InvoiceFacade
     */
    private $invoiceFacade;

    /**
     * @var QuoteFacade
     */
    private $quoteFacade;

    /**
     * @var TicketFacade
     */
    private $ticketFacade;

    /**
     * @var JobFacade
     */
    private $jobFacade;

    /**
     * @var ClientFacade
     */
    private $clientFacade;

    /**
     * @var PaymentPlanFacade
     */
    private $paymentPlanFacade;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        string $rootDir,
        Options $options,
        KernelInterface $kernel,
        Connection $connection,
        Filesystem $filesystem,
        QueueCleaner $rabbitQueueCleaner,
        ServiceStatusUpdater $serviceStatusUpdater,
        ClientStatusUpdater $clientStatusUpdater,
        ServiceOutageUpdater $serviceOutageUpdater,
        OptionsFacade $optionsFacade,
        EntityManagerInterface $entityManager,
        PortsBump $portsBump,
        UasBump $uasBump,
        MaintenanceFileManager $maintenanceFileManager,
        PaymentFacade $paymentFacade,
        RefundFacade $refundFacade,
        ServiceFacade $serviceFacade,
        InvoiceFacade $invoiceFacade,
        QuoteFacade $quoteFacade,
        TicketFacade $ticketFacade,
        JobFacade $jobFacade,
        ClientFacade $clientFacade,
        PaymentPlanFacade $paymentPlanFacade,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->rootDir = $rootDir;
        $this->options = $options;
        $this->kernel = $kernel;
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->finder = new Finder();
        $this->rabbitQueueCleaner = $rabbitQueueCleaner;
        $this->serviceStatusUpdater = $serviceStatusUpdater;
        $this->clientStatusUpdater = $clientStatusUpdater;
        $this->serviceOutageUpdater = $serviceOutageUpdater;
        $this->optionsFacade = $optionsFacade;
        $this->entityManager = $entityManager;
        $this->portsBump = $portsBump;
        $this->uasBump = $uasBump;
        $this->maintenanceFileManager = $maintenanceFileManager;
        $this->paymentFacade = $paymentFacade;
        $this->refundFacade = $refundFacade;
        $this->serviceFacade = $serviceFacade;
        $this->invoiceFacade = $invoiceFacade;
        $this->quoteFacade = $quoteFacade;
        $this->ticketFacade = $ticketFacade;
        $this->jobFacade = $jobFacade;
        $this->clientFacade = $clientFacade;
        $this->paymentPlanFacade = $paymentPlanFacade;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;

        $this->factoryResetFile = sprintf(
            '%s/internal/do_factory_reset',
            $this->rootDir
        );
    }

    public function requestTermination(SandboxTerminationData $sandboxTerminationData): bool
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($this->factoryResetFile)) {
            throw new ResetException('Already requested: ' . $this->factoryResetFile);
        }
        $this->maintenanceFileManager->enterMaintenanceMode();

        $factoryResetData = [];
        foreach ($sandboxTerminationData as $key => $value) {
            $factoryResetData[$key] = $value;
        }

        $filesystem->dumpFile(
            $this->factoryResetFile,
            Json::encode($factoryResetData)
        );

        return true;
    }

    public function terminate(SandboxTerminationData $sandboxTerminationData): void
    {
        switch ($sandboxTerminationData->mode) {
            case SandboxTerminationData::MODE_ALL:
                $this->terminateAll();
                break;
            case SandboxTerminationData::MODE_SMART:
            default:
                $this->terminateSmart($sandboxTerminationData);
                break;
        }
    }

    public function terminateAll(): void
    {
        $apiToken = $this->options->getGeneral(General::CRM_API_TOKEN);

        $this->deleteFilesInDirectory($this->rootDir . '/data/invoices');
        $this->deleteFilesInDirectory($this->rootDir . '/data/quotes');
        $this->deleteFilesInDirectory($this->rootDir . '/data/scheduling');
        $this->deleteFilesInDirectory($this->rootDir . '/data/ticketing');
        $this->deleteFilesInDirectory($this->rootDir . '/data/payment_receipts');
        $this->deleteFilesInDirectory($this->rootDir . '/../web/media');
        $this->deleteFilesInDirectory($this->rootDir . '/../web/uploads');
        $this->deleteFilesInDirectory($this->rootDir . '/data/documents');
        $this->deleteFilesInDirectory($this->rootDir . '/data/invoice_templates');
        $this->deleteFilesInDirectory($this->rootDir . '/data/proforma_invoice_templates');
        $this->deleteFilesInDirectory($this->rootDir . '/data/quote_templates');
        $this->deleteFilesInDirectory($this->rootDir . '/data/account_statement_templates');
        $this->deleteFilesInDirectory($this->rootDir . '/data/payment_receipt_templates');
        $this->deleteFilesInDirectory($this->rootDir . '/data/download');
        $this->deleteFilesInDirectory($this->rootDir . '/data/email_queue');
        $this->deleteFilesInDirectory($this->rootDir . '/EmailQueue/spool');
        $this->deleteFilesInDirectory($this->rootDir . '/data/plugins');
        $this->deleteFilesInDirectory($this->rootDir . '/../web/_plugins');

        $this->connection->transactional(
            function () {
                $this->connection->query('DROP SCHEMA public CASCADE');
                $this->connection->query('CREATE SCHEMA public');

                $application = new Application($this->kernel);
                $application->setAutoExit(false);
                $application->run(
                    new ArrayInput(
                        [
                            'command' => 'doctrine:migrations:migrate',
                            '--no-interaction' => true,
                            '--no-debug' => true,
                            '--quiet' => true,
                        ]
                    ),
                    new ConsoleOutput()
                );
            }
        );

        $this->purgeRabbitQueues(self::RABBIT_QUEUES);

        $this->entityManager->clear(General::class);
        $this->entityManager->clear(Option::class);
        $this->portsBump->update();
        $this->uasBump->update();
        $this->optionsFacade->updateGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE, '1');
        $this->optionsFacade->updateGeneral(General::CRM_API_TOKEN, $apiToken);
    }

    public function terminateFromConfigFile(string $configFile): void
    {
        $filesystem = new Filesystem();
        if (! $filesystem->exists($configFile)) {
            throw new ResetException('Config not found: ' . $configFile);
        }
        $this->terminateFromStringConfig(file_get_contents($configFile));
    }

    private function terminateFromStringConfig(?string $resetConfig): void
    {
        $this->terminate($this->parseTerminationData($resetConfig));
    }

    private function refreshClient(Client $client): ?Client
    {
        if (! $this->entityManager->contains($client)) {
            $client = $this->entityManager->find(Client::class, $client->getId());
        }

        return $client;
    }

    private function terminateSmart(SandboxTerminationData $sandboxTerminationData): void
    {
        $this->rabbitMqEnqueuer->disable();

        if (! $sandboxTerminationData->keepClients) {
            $clients = $this->entityManager->getRepository(Client::class)->findAll();
            foreach ($clients as $client) {
                $client = $this->refreshClient($client);
                if (! $client) {
                    continue;
                }

                foreach ($client->getActivePaymentPlans() as $paymentPlan) {
                    try {
                        $this->paymentPlanFacade->cancelSubscription($paymentPlan, false);
                    } catch (PaymentPlanException $paymentPlanException) {
                        $this->paymentPlanFacade->deleteSubscription($paymentPlan, false);
                    }
                }

                $this->entityManager->clear();

                $client = $this->refreshClient($client);
                if (! $client) {
                    continue;
                }

                $this->clientFacade->handleDelete($client);
                $this->entityManager->clear();
            }

            $this->connection->query('DELETE FROM ip_accounting');
            $this->connection->query('DELETE FROM ip_accounting_raw');
            $this->connection->query('DELETE FROM service_accounting');
            $this->connection->query('DELETE FROM service_accounting_raw');
            $this->connection->query('DELETE FROM service_accounting_correction');

            $this->deleteFilesInDirectory($this->rootDir . '/data/documents');
        } elseif ($sandboxTerminationData->resetInvitationEmails) {
            $this->connection->query('UPDATE client SET invitation_email_sent_date = NULL');
        }

        if (! $sandboxTerminationData->keepPayments || ! $sandboxTerminationData->keepClients) {
            $this->refundFacade->handleDeleteMultiple($this->entityManager->getRepository(Refund::class)->findAll());
            $this->entityManager->clear();
            $this->paymentFacade->handleDeleteMultiple($this->entityManager->getRepository(Payment::class)->findAll());
            $this->entityManager->clear();

            $this->deleteFilesInDirectory($this->rootDir . '/data/payment_receipts');
        }

        if (! $sandboxTerminationData->keepInvoices || ! $sandboxTerminationData->keepClients || ! $sandboxTerminationData->keepServices) {
            $this->invoiceFacade->handleDeleteMultiple($this->entityManager->getRepository(Invoice::class)->findAll());
            $this->entityManager->clear();
            $this->quoteFacade->handleDeleteMultiple($this->entityManager->getRepository(Quote::class)->findAll());
            $this->connection->query('DELETE FROM fee');
            if ($sandboxTerminationData->resetNextInvoicingDay) {
                $this->connection->query('UPDATE service SET invoicing_last_period_end = NULL');
                $this->entityManager->clear();
                foreach ($this->entityManager->getRepository(Service::class)->findAll() as $service) {
                    $service->setNextInvoicingDay(Invoicing::getNextInvoicingDay($service));
                }
                $this->entityManager->flush();
            }
            $this->entityManager->clear();

            $this->deleteFilesInDirectory($this->rootDir . '/data/invoices');
            $this->deleteFilesInDirectory($this->rootDir . '/data/quotes');
        } elseif ($sandboxTerminationData->resetInvoiceEmails) {
            $this->connection->query('UPDATE invoice SET email_sent_date = NULL');
        }

        if (! $sandboxTerminationData->keepTickets || ! $sandboxTerminationData->keepClients) {
            $this->ticketFacade->handleDeleteMultiple($this->entityManager->getRepository(Ticket::class)->findAll());
            $this->entityManager->clear();
            $this->deleteFilesInDirectory($this->rootDir . '/data/ticketing');
        }

        if (! $sandboxTerminationData->keepJobs) {
            $this->jobFacade->handleDeleteMultiple($this->entityManager->getRepository(Job::class)->findAll());
            $this->entityManager->clear();
            $this->deleteFilesInDirectory($this->rootDir . '/data/scheduling');
        }

        if (! $sandboxTerminationData->keepServices || ! $sandboxTerminationData->keepClients) {
            foreach ($this->entityManager->getRepository(Service::class)->findAll() as $service) {
                $this->serviceFacade->handleDelete($service, false);
            }
            $this->entityManager->clear();
        }

        $this->purgeRabbitQueues(self::RABBIT_QUEUES_SMART);

        $this->connection->update(
            'general',
            [
                'value' => '0',
            ],
            [
                'code' => 'sandbox_mode',
            ]
        );

        $this->entityManager->clear();
        $this->rabbitMqEnqueuer->enable();

        $this->serviceStatusUpdater->updateServices();
        $this->clientStatusUpdater->update();
        $this->serviceOutageUpdater->update();
    }

    private function purgeRabbitQueues(array $queues): void
    {
        foreach ($queues as $name) {
            $this->rabbitQueueCleaner->purgeQueue($name);
        }
    }

    private function deleteFilesInDirectory(string $directory): void
    {
        if (! $this->filesystem->exists($directory)) {
            return;
        }

        $files = $this->finder->in($directory);
        $this->filesystem->remove($files);
    }

    private function parseTerminationData(?string $resetConfig): SandboxTerminationData
    {
        $sandboxTerminationData = new SandboxTerminationData();
        if (empty($resetConfig)) {
            // backward compat - full reset
            $sandboxTerminationData->mode = SandboxTerminationData::MODE_ALL;
        } else {
            try {
                $jsonData = Json::decode($resetConfig, false);
            } catch (JsonException $jse) {
                throw new ResetException('Invalid config: ' . $jse->getMessage(), $jse->getCode(), $jse);
            }
            if (! empty($jsonData)) {
                foreach ($jsonData as $key => $value) {
                    if (property_exists(SandboxTerminationData::class, $key)) {
                        $sandboxTerminationData->$key = $value;
                    }
                }
            }
        }

        return $sandboxTerminationData;
    }
}
