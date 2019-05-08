<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\DataProvider\PluginListDataProvider;
use AppBundle\Entity\Option;
use AppBundle\Event\Backup\BackupsDeleteEvent;
use AppBundle\Event\Backup\BackupsUploadEvent;
use AppBundle\FileManager\BackupFileManager;
use AppBundle\FileManager\PluginFileManager;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class BackupCreator
{
    private const DIR_DOCUMENTS = 'documents';
    private const DIR_DOWNLOAD = 'download';
    private const DIR_INVOICE_TEMPLATES = 'invoice_templates';
    private const DIR_PROFORMA_INVOICE_TEMPLATES = 'proforma_invoice_templates';
    private const DIR_QUOTE_TEMPLATES = 'quote_templates';
    private const DIR_ACCOUNT_STATEMENT_TEMPLATES = 'account_statement_templates';
    private const DIR_PAYMENT_RECEIPT_TEMPLATES = 'payment_receipt_templates';
    private const DIR_MEDIA = 'media';
    private const DIR_SSL = 'ssl';
    private const DIR_UPLOADS = 'uploads';
    private const DIR_WEBROOT = 'webroot';
    private const DIR_PLUGINS = 'plugins';
    private const DIR_TICKET_ATTACHMENTS = 'ticketing/attachments';
    private const DIR_JOB_ATTACHMENTS = 'scheduling/attachments';
    private const DIR_CUSTOMIZATION = 'customization';
    private const FILE_CRYPTO_KEY = 'crypto.key';
    private const FILE_VERSION = 'version';

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $invoiceTemplatesPath;

    /**
     * @var string
     */
    private $proformaInvoiceTemplatesPath;

    /**
     * @var string
     */
    private $quoteTemplatesPath;

    /**
     * @var string
     */
    private $accountStatementTemplatesPath;

    /**
     * @var string
     */
    private $paymentReceiptTemplatesPath;

    /**
     * @var string
     */
    private $sslPath;

    /**
     * @var string
     */
    private $downloadsPath;

    /**
     * @var string
     */
    private $mediaPath;

    /**
     * @var string
     */
    private $uploadsPath;

    /**
     * @var string
     */
    private $webrootPath;

    /**
     * @var string
     */
    private $documentsPath;

    /**
     * @var string
     */
    private $pluginsPath;

    /**
     * @var string
     */
    private $ticketAttachmentsPath;

    /**
     * @var string
     */
    private $jobAttachmentsPath;

    /**
     * @var string
     */
    private $databasePath;

    /**
     * @var string
     */
    private $customCssPath;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PluginListDataProvider
     */
    private $pluginListDataProvider;

    public function __construct(
        string $version,
        string $invoiceTemplatesPath,
        string $proformaInvoiceTemplatesPath,
        string $accountStatementTemplatesPath,
        string $quoteTemplatesPath,
        string $paymentReceiptTemplatesPath,
        string $sslPath,
        string $downloadsPath,
        string $mediaPath,
        string $uploadsPath,
        string $webrootPath,
        string $documentsPath,
        string $pluginsPath,
        string $ticketAttachmentsPath,
        string $jobAttachmentsPath,
        string $databasePath,
        string $customCssPath,
        Options $options,
        Filesystem $filesystem,
        Encryption $encryption,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        PluginListDataProvider $pluginListDataProvider
    ) {
        $this->version = $version;
        $this->invoiceTemplatesPath = $invoiceTemplatesPath;
        $this->proformaInvoiceTemplatesPath = $proformaInvoiceTemplatesPath;
        $this->quoteTemplatesPath = $quoteTemplatesPath;
        $this->accountStatementTemplatesPath = $accountStatementTemplatesPath;
        $this->paymentReceiptTemplatesPath = $paymentReceiptTemplatesPath;
        $this->sslPath = $sslPath;
        $this->downloadsPath = $downloadsPath;
        $this->mediaPath = $mediaPath;
        $this->uploadsPath = $uploadsPath;
        $this->webrootPath = $webrootPath;
        $this->documentsPath = $documentsPath;
        $this->pluginsPath = $pluginsPath;
        $this->ticketAttachmentsPath = $ticketAttachmentsPath;
        $this->jobAttachmentsPath = $jobAttachmentsPath;
        $this->databasePath = $databasePath;
        $this->customCssPath = $customCssPath;
        $this->options = $options;
        $this->filesystem = $filesystem;
        $this->encryption = $encryption;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->pluginListDataProvider = $pluginListDataProvider;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function create(string $backupDirectory): void
    {
        $backupDirectory = rtrim($backupDirectory, '/');
        if (! is_dir($backupDirectory) && ! is_writable($backupDirectory)) {
            throw new \InvalidArgumentException(
                sprintf('Directory "%s" does not exist or is not writable.', $backupDirectory)
            );
        }

        $this->includeVersion($backupDirectory);

        $this->includeCryptoKey($backupDirectory);

        if ($this->options->get(Option::BACKUP_INCLUDE_INVOICE_TEMPLATES)) {
            $this->mirror(
                $this->invoiceTemplatesPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_INVOICE_TEMPLATES)
            );
            $this->mirror(
                $this->proformaInvoiceTemplatesPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_PROFORMA_INVOICE_TEMPLATES)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_QUOTE_TEMPLATES)) {
            $this->mirror(
                $this->quoteTemplatesPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_QUOTE_TEMPLATES)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES)) {
            $this->mirror(
                $this->accountStatementTemplatesPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_ACCOUNT_STATEMENT_TEMPLATES)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES)) {
            $this->mirror(
                $this->paymentReceiptTemplatesPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_PAYMENT_RECEIPT_TEMPLATES)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_SSL_CERTIFICATES)) {
            $this->mirror(
                $this->sslPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_SSL)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_DOWNLOADS)) {
            $this->mirror(
                $this->downloadsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_DOWNLOAD)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_MEDIA)) {
            $this->mirror(
                $this->mediaPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_MEDIA)
            );

            $this->mirror(
                $this->uploadsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_UPLOADS)
            );

            if ($this->filesystem->exists($this->customCssPath)) {
                $this->filesystem->copy(
                    $this->customCssPath,
                    sprintf('%s/%s/custom.css', $backupDirectory, self::DIR_CUSTOMIZATION)
                );
            }
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_WEBROOT)) {
            $this->mirror(
                $this->webrootPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_WEBROOT)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_DOCUMENTS)) {
            $this->mirror(
                $this->documentsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_DOCUMENTS)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_PLUGINS)) {
            $this->mirrorPlugins(
                $this->pluginsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_PLUGINS)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_TICKET_ATTACHMENTS)) {
            $this->mirror(
                $this->ticketAttachmentsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_TICKET_ATTACHMENTS)
            );
        }

        if ($this->options->get(Option::BACKUP_INCLUDE_JOB_ATTACHMENTS)) {
            $this->mirror(
                $this->jobAttachmentsPath,
                sprintf('%s/%s', $backupDirectory, self::DIR_JOB_ATTACHMENTS)
            );
        }

        // Do not call upload() here, the database backup is added via pg_dump in backup_create.sh.
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function cleanup(): void
    {
        $files = $this->getFilesToUploadAndDelete();
        if ($files['delete']) {
            $this->filesystem->remove($files['delete']);
            $this->eventDispatcher->dispatch(
                BackupsDeleteEvent::class,
                new BackupsDeleteEvent($files['delete'])
            );
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function upload(): void
    {
        $files = $this->getFilesToUploadAndDelete();
        if ($files['upload']) {
            $this->eventDispatcher->dispatch(
                BackupsUploadEvent::class,
                new BackupsUploadEvent($files['upload'])
            );
        }
    }

    private function getFilesToUploadAndDelete(): array
    {
        $backupFiles = (new Finder())
            ->files()
            ->in($this->databasePath)
            ->sort(
                function (\SplFileInfo $a, \SplFileInfo $b) {
                    // Sort by modified time, descending.

                    return $b->getMTime() <=> $a->getMTime();
                }
            );

        $filesToUpload = [];
        $filesToDelete = [];
        $maxFilesToKeep = $this->options->get(Option::BACKUP_LIFETIME_COUNT);

        $filesToKeep = $maxFilesToKeep;
        foreach ($backupFiles->getIterator() as $backupFile) {
            if ($maxFilesToKeep > 0) {
                if ($filesToKeep > 0) {
                    $filesToUpload[] = $backupFile->getPathname();
                    --$filesToKeep;
                } elseif ($this->isBackup($backupFile)) {
                    $filesToDelete[] = $backupFile->getPathname();
                } // else ignore - do not upload, do not delete
            } else {
                $filesToUpload[] = $backupFile->getPathname();
            }
        }

        return [
            'upload' => $filesToUpload,
            'delete' => $filesToDelete,
        ];
    }

    private function includeVersion(string $backupDirectory): void
    {
        $this->filesystem->dumpFile(
            sprintf('%s/%s', $backupDirectory, self::FILE_VERSION),
            $this->version
        );
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function includeCryptoKey(string $backupDirectory): void
    {
        $cryptoKey = $this->encryption->getKey();
        if ($cryptoKey !== null) {
            $this->filesystem->dumpFile(
                sprintf('%s/%s', $backupDirectory, self::FILE_CRYPTO_KEY),
                $cryptoKey->saveToAsciiSafeString()
            );
        }
    }

    private function mirror(string $originDir, string $targetDir): void
    {
        if (! $this->filesystem->exists($originDir)) {
            return;
        }

        $this->filesystem->mirror($originDir, $targetDir);
    }

    private function mirrorPlugins(string $originDir, string $targetDir): void
    {
        $plugins = $this->pluginListDataProvider->getInstalledPlugins();

        foreach ($plugins as $plugin) {
            $pluginName = $plugin->name;

            if (! is_dir($originDir . DIRECTORY_SEPARATOR . $pluginName)) {
                continue;
            }

            try {
                $this->filesystem->mirror(
                    $originDir . DIRECTORY_SEPARATOR . $pluginName,
                    $targetDir . DIRECTORY_SEPARATOR . $pluginName,
                    (new Finder())
                        ->in($originDir . DIRECTORY_SEPARATOR . $pluginName)
                        ->notName(PluginFileManager::FILE_INTERNAL_RUNNING_LOCK)
                        ->notName(PluginFileManager::FILE_INTERNAL_EXECUTION_REQUESTED)
                        ->getIterator()
                );
            } catch (IOException $ioe) {
                $this->logger->warning(sprintf('Failed to backup plugin %s: %s', $pluginName, $ioe->getMessage()));
            }
        }
    }

    private function isBackup(\SplFileInfo $backupFile): bool
    {
        return (bool) Strings::match($backupFile->getBasename(), BackupFileManager::BACKUP_DATABASE_REGEX);
    }
}
