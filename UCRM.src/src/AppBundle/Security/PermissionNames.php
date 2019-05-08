<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Security;

use AppBundle\Controller\AccountStatementTemplateController;
use AppBundle\Controller\AppearanceController;
use AppBundle\Controller\AppKeyController;
use AppBundle\Controller\BackupController;
use AppBundle\Controller\CertificateController;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\ClientImportController;
use AppBundle\Controller\ClientTagController;
use AppBundle\Controller\ClientZonePageController;
use AppBundle\Controller\ContactTypeController;
use AppBundle\Controller\CustomAttributeController;
use AppBundle\Controller\DeviceController;
use AppBundle\Controller\DeviceInterfaceController;
use AppBundle\Controller\DeviceLogController;
use AppBundle\Controller\DocumentController;
use AppBundle\Controller\DownloadController;
use AppBundle\Controller\EmailLogController;
use AppBundle\Controller\EmailTemplatesController;
use AppBundle\Controller\EntityLogController;
use AppBundle\Controller\FccReportsController;
use AppBundle\Controller\InvoiceController;
use AppBundle\Controller\InvoicedRevenueController;
use AppBundle\Controller\InvoiceTemplateController;
use AppBundle\Controller\MailingController;
use AppBundle\Controller\NetworkMapController;
use AppBundle\Controller\NotificationSettingsController;
use AppBundle\Controller\OrganizationBankAccountController;
use AppBundle\Controller\OrganizationController;
use AppBundle\Controller\OutageController;
use AppBundle\Controller\PaymentController;
use AppBundle\Controller\PaymentImportController;
use AppBundle\Controller\PaymentReceiptTemplateController;
use AppBundle\Controller\PluginController;
use AppBundle\Controller\ProductController;
use AppBundle\Controller\ProformaInvoiceTemplateController;
use AppBundle\Controller\QuoteController;
use AppBundle\Controller\QuoteTemplateController;
use AppBundle\Controller\RefundController;
use AppBundle\Controller\ReportDataUsageController;
use AppBundle\Controller\SandboxTerminationController;
use AppBundle\Controller\ServiceController;
use AppBundle\Controller\ServiceStopReasonController;
use AppBundle\Controller\SettingBillingController;
use AppBundle\Controller\SettingController;
use AppBundle\Controller\SettingFeesController;
use AppBundle\Controller\SettingSuspendController;
use AppBundle\Controller\SiteController;
use AppBundle\Controller\SurchargeController;
use AppBundle\Controller\SuspensionTemplatesController;
use AppBundle\Controller\TariffController;
use AppBundle\Controller\TaxController;
use AppBundle\Controller\TaxReportController;
use AppBundle\Controller\UnknownDevicesController;
use AppBundle\Controller\UpdatesController;
use AppBundle\Controller\UserController;
use AppBundle\Controller\UserGroupController;
use AppBundle\Controller\VendorController;
use AppBundle\Controller\WebhookEndpointController;
use AppBundle\Controller\WebrootController;
use AppBundle\Entity\UserGroup;
use SchedulingBundle\Security\SchedulingPermissions;
use TicketingBundle\Controller\TicketController;

/**
 * @see UserGroupController::getModulePermissionGroups()
 * @see UserGroup::PERMISSION_MODULES
 * @see UserGroup::SPECIAL_PERMISSIONS
 * @see SpecialPermission
 */
class PermissionNames
{
    /**
     * Don't change these values. Doing so would be a BC break for plugins.
     */
    public const PERMISSION_SYSTEM_NAMES = [
        ClientController::class => 'clients/clients',
        DocumentController::class => 'clients/documents',
        ServiceController::class => 'clients/services',
        InvoiceController::class => 'billing/invoices',
        QuoteController::class => 'billing/quotes',
        PaymentController::class => 'billing/payments',
        RefundController::class => 'billing/refunds',
        SchedulingPermissions::JOBS_MY => 'scheduling/my_jobs',
        SchedulingPermissions::JOBS_ALL => 'scheduling/all_jobs',
        TicketController::class => 'ticketing/ticketing',
        SiteController::class => 'network/sites',
        DeviceController::class => 'network/devices',
        DeviceInterfaceController::class => 'network/device_interfaces',
        OutageController::class => 'network/outages',
        UnknownDevicesController::class => 'network/unknown_devices',
        NetworkMapController::class => 'network/network_map',
        TaxReportController::class => 'reports/taxes',
        InvoicedRevenueController::class => 'reports/invoiced_revenue',
        ReportDataUsageController::class => 'reports/data_usage',
        OrganizationController::class => 'system/organizations',
        SettingController::class => 'system/settings',
        WebhookEndpointController::class => 'system/webhooks',
        PluginController::class => 'system/plugins',
        TariffController::class => 'system/items/service_plans',
        ProductController::class => 'system/items/products',
        SurchargeController::class => 'system/items/surcharges',
        SettingBillingController::class => 'system/billing/invoicing',
        SettingSuspendController::class => 'system/billing/suspension',
        SettingFeesController::class => 'system/billing/fees',
        OrganizationBankAccountController::class => 'system/billing/organization_bank_accounts',
        TaxController::class => 'system/billing/taxes',
        EmailTemplatesController::class => 'system/customization/email_templates',
        SuspensionTemplatesController::class => 'system/customization/suspension_templates',
        NotificationSettingsController::class => 'system/customization/notification_settings',
        InvoiceTemplateController::class => 'system/customization/invoice_templates',
        ProformaInvoiceTemplateController::class => 'system/customization/proforma_invoice_templates',
        AccountStatementTemplateController::class => 'system/customization/account_statement_templates',
        QuoteTemplateController::class => 'system/customization/quote_templates',
        PaymentReceiptTemplateController::class => 'system/customization/payment_receipt_templates',
        AppearanceController::class => 'system/customization/appearance',
        ClientZonePageController::class => 'system/customization/client_zone_pages',
        BackupController::class => 'system/tools/backup',
        ClientImportController::class => 'system/tools/client_import',
        PaymentImportController::class => 'system/tools/payment_import',
        CertificateController::class => 'system/tools/ssl_certificate',
        WebrootController::class => 'system/tools/webroot',
        DownloadController::class => 'system/tools/downloads',
        FccReportsController::class => 'system/tools/fcc_reports',
        UpdatesController::class => 'system/tools/updates',
        MailingController::class => 'system/tools/mailing',
        UserController::class => 'system/security/users',
        UserGroupController::class => 'system/security/groups_and_permissions',
        AppKeyController::class => 'system/security/app_keys',
        DeviceLogController::class => 'system/logs/device_log',
        EmailLogController::class => 'system/logs/email_log',
        EntityLogController::class => 'system/logs/system_log',
        VendorController::class => 'system/other/vendors',
        ServiceStopReasonController::class => 'system/other/reasons_for_suspending_service',
        CustomAttributeController::class => 'system/other/custom_attributes',
        ClientTagController::class => 'system/other/client_tags',
        ContactTypeController::class => 'system/other/contact_types',
        SandboxTerminationController::class => 'system/other/sandbox_termination',
    ];

    public const PERMISSION_HUMAN_NAMES = [
        AccountStatementTemplateController::class => 'Account statement template',
        AppearanceController::class => 'Appearance',
        AppKeyController::class => 'App key',
        BackupController::class => 'Backup',
        CertificateController::class => 'SSL certificate',
        ClientController::class => 'Client',
        ClientTagController::class => 'Client tags',
        ContactTypeController::class => 'Contact types',
        CustomAttributeController::class => 'Custom attributes',
        DeviceController::class => 'Device',
        DeviceInterfaceController::class => 'Device interface',
        DeviceLogController::class => 'Device log',
        DocumentController::class => 'Document',
        DownloadController::class => 'Download',
        EmailLogController::class => 'Email log',
        EmailTemplatesController::class => 'Email templates',
        EntityLogController::class => 'System log',
        FccReportsController::class => 'FCC reports',
        ClientImportController::class => 'Clients import',
        PaymentImportController::class => 'Payments import',
        InvoiceController::class => 'Invoice',
        QuoteController::class => 'Quote',
        InvoicedRevenueController::class => 'Invoiced revenue',
        InvoiceTemplateController::class => 'Invoice templates',
        ProformaInvoiceTemplateController::class => 'Proforma invoice templates',
        QuoteTemplateController::class => 'Quote templates',
        PaymentReceiptTemplateController::class => 'Receipt templates',
        SchedulingPermissions::JOBS_ALL => 'All users jobs',
        SchedulingPermissions::JOBS_MY => 'My jobs',
        MailingController::class => 'Mailing',
        NetworkMapController::class => 'Network map',
        NotificationSettingsController::class => 'Notification settings',
        OrganizationBankAccountController::class => 'Organization bank account',
        OrganizationController::class => 'Organization',
        OutageController::class => 'Outages',
        PaymentController::class => 'Payment',
        PluginController::class => 'Plugins',
        ProductController::class => 'Product',
        RefundController::class => 'Refund',
        ReportDataUsageController::class => 'Data usage reports',
        SandboxTerminationController::class => 'Demo termination',
        ServiceController::class => 'Service',
        ServiceStopReasonController::class => 'Service stop reason',
        SettingController::class => 'Settings',
        SettingBillingController::class => 'Invoicing',
        ClientZonePageController::class => 'Client zone pages',
        SettingSuspendController::class => 'Suspend',
        SettingFeesController::class => 'Fees',
        SiteController::class => 'Site',
        SurchargeController::class => 'Surcharge',
        SuspensionTemplatesController::class => 'Suspension templates',
        TariffController::class => 'Service plan',
        TaxController::class => 'Tax',
        TaxReportController::class => 'Tax report',
        TicketController::class => 'Ticketing',
        UnknownDevicesController::class => 'Unknown devices',
        UpdatesController::class => 'Updates',
        UserController::class => 'User',
        UserGroupController::class => 'User group',
        VendorController::class => 'Vendor',
        WebhookEndpointController::class => 'Webhooks',
        WebrootController::class => 'Webroot',
    ];

    /**
     * Don't change these values. Doing so would be a BC break for plugins.
     */
    public const SPECIAL_PERMISSIONS_SYSTEM_NAMES = [
        SpecialPermission::CLIENT_ACCOUNT_STANDING => 'special/clients_financial_information',
        SpecialPermission::CLIENT_EXPORT => 'special/client_export',
        SpecialPermission::CLIENT_IMPERSONATION => 'special/client_impersonation',
        SpecialPermission::CLIENT_LOG_EDIT => 'special/client_log_edit_delete',
        SpecialPermission::JOB_COMMENT_EDIT => 'special/job_comment_edit_delete',
        SpecialPermission::SHOW_DEVICE_PASSWORDS => 'special/show_device_passwords',
        SpecialPermission::FINANCIAL_OVERVIEW => 'special/view_financial_information',
        SpecialPermission::PAYMENT_CREATE => 'special/payment_create',
    ];

    public const SPECIAL_PERMISSIONS_HUMAN_NAMES = [
        SpecialPermission::CLIENT_ACCOUNT_STANDING => 'Client\'s financial information',
        SpecialPermission::CLIENT_EXPORT => 'Client export',
        SpecialPermission::CLIENT_IMPERSONATION => 'Client impersonation',
        SpecialPermission::CLIENT_LOG_EDIT => 'Client log edit / delete',
        SpecialPermission::JOB_COMMENT_EDIT => 'Job comment edit / delete',
        SpecialPermission::SHOW_DEVICE_PASSWORDS => 'Show device passwords',
        SpecialPermission::FINANCIAL_OVERVIEW => 'View financial information',
        SpecialPermission::PAYMENT_CREATE => 'Allow creating payments',
    ];
}
