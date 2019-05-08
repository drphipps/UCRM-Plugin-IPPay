<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class BackupData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_INVOICE_TEMPLATES)
     */
    public $backupIncludeInvoiceTemplates;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_QUOTE_TEMPLATES)
     */
    public $backupIncludeQuoteTemplates;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES)
     */
    public $backupIncludeAccountStatementTemplates;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES)
     */
    public $backupIncludePaymentReceiptTemplates;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_SSL_CERTIFICATES)
     */
    public $backupIncludeSslCertificates;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_DOWNLOADS)
     */
    public $backupIncludeDownloads;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_MEDIA)
     */
    public $backupIncludeMedia;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_WEBROOT)
     */
    public $backupIncludeWebroot;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_DOCUMENTS)
     */
    public $backupIncludeDocuments;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_PLUGINS)
     */
    public $backupIncludePlugins;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_TICKET_ATTACHMENTS)
     */
    public $backupIncludeTicketAttachments;

    /**
     * @var bool
     *
     * @Identifier(Option::BACKUP_INCLUDE_JOB_ATTACHMENTS)
     */
    public $backupIncludeJobAttachments;
}
