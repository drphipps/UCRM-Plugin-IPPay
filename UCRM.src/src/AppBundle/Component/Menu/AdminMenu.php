<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Menu;

use AppBundle\Controller\AccountStatementTemplateController;
use AppBundle\Controller\AppearanceController;
use AppBundle\Controller\AppKeyController;
use AppBundle\Controller\BackupController;
use AppBundle\Controller\BillingController;
use AppBundle\Controller\BillingInvoiceController;
use AppBundle\Controller\BillingQuoteController;
use AppBundle\Controller\CertificateController;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\ClientImportCancelController;
use AppBundle\Controller\ClientImportController;
use AppBundle\Controller\ClientImportMappingController;
use AppBundle\Controller\ClientImportPreviewController;
use AppBundle\Controller\ClientImportSaveController;
use AppBundle\Controller\ClientImportUploadController;
use AppBundle\Controller\ClientTagController;
use AppBundle\Controller\ClientZonePageController;
use AppBundle\Controller\ContactTypeController;
use AppBundle\Controller\CustomAttributeController;
use AppBundle\Controller\DeviceController;
use AppBundle\Controller\DeviceInterfaceController;
use AppBundle\Controller\DeviceLogController;
use AppBundle\Controller\DocumentController;
use AppBundle\Controller\DownloadController;
use AppBundle\Controller\DraftController;
use AppBundle\Controller\EmailLogController;
use AppBundle\Controller\EmailTemplatesController;
use AppBundle\Controller\EntityLogController;
use AppBundle\Controller\FccReportsController;
use AppBundle\Controller\HomepageController;
use AppBundle\Controller\InvoiceController;
use AppBundle\Controller\InvoicedRevenueController;
use AppBundle\Controller\InvoiceFormController;
use AppBundle\Controller\InvoiceTemplateController;
use AppBundle\Controller\MailingController;
use AppBundle\Controller\NetworkMapController;
use AppBundle\Controller\NotificationSettingsController;
use AppBundle\Controller\OrganizationBankAccountController;
use AppBundle\Controller\OrganizationController;
use AppBundle\Controller\OrganizationSettingController;
use AppBundle\Controller\OutageController;
use AppBundle\Controller\PaymentController;
use AppBundle\Controller\PaymentImportController;
use AppBundle\Controller\PaymentReceiptTemplateController;
use AppBundle\Controller\PluginController;
use AppBundle\Controller\PluginListController;
use AppBundle\Controller\ProductController;
use AppBundle\Controller\ProformaInvoiceTemplateController;
use AppBundle\Controller\QuoteController;
use AppBundle\Controller\QuoteFormController;
use AppBundle\Controller\QuoteTemplateController;
use AppBundle\Controller\RefundController;
use AppBundle\Controller\ReportDataUsageController;
use AppBundle\Controller\ServiceController;
use AppBundle\Controller\ServiceFormController;
use AppBundle\Controller\ServiceStopReasonController;
use AppBundle\Controller\SettingApplicationController;
use AppBundle\Controller\SettingBillingController;
use AppBundle\Controller\SettingClientZoneController;
use AppBundle\Controller\SettingFeesController;
use AppBundle\Controller\SettingLocalizationController;
use AppBundle\Controller\SettingLogsController;
use AppBundle\Controller\SettingMailerController;
use AppBundle\Controller\SettingNetFlowController;
use AppBundle\Controller\SettingOAuthController;
use AppBundle\Controller\SettingOutageController;
use AppBundle\Controller\SettingQosController;
use AppBundle\Controller\SettingSuspendController;
use AppBundle\Controller\SettingSyncController;
use AppBundle\Controller\SettingTicketingController;
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
use AppBundle\Controller\WebhookLogController;
use AppBundle\Controller\WebrootController;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionGrantedChecker;
use SchedulingBundle\Controller\AgendaController;
use SchedulingBundle\Controller\JobController;
use SchedulingBundle\Controller\TimelineController;
use SchedulingBundle\Security\SchedulingPermissions;
use TicketingBundle\Controller\TicketController;

final class AdminMenu
{
    /**
     * @var PermissionGrantedChecker
     */
    private $permissionGrantedChecker;

    public function __construct(PermissionGrantedChecker $permissionGrantedChecker)
    {
        $this->permissionGrantedChecker = $permissionGrantedChecker;
    }

    /**
     * Builds application menu for admin.
     */
    public function assemble(): MenuBuilder
    {
        $builder = new MenuBuilder();

        $builder->enablePermissionCheck($this->permissionGrantedChecker);

        $builder->addLink(
            'Dashboard',
            [
                'homepage' => [
                    'controller' => HomepageController::class,
                    'permission' => Permission::GUEST,
                ],
            ],
            [
                HomepageController::class,
            ],
            'ucrm-icon--dashboard'
        );

        $builder->addLink(
            'Clients',
            [
                'client_index' => ClientController::class,
            ],
            [
                ClientController::class,
                DocumentController::class,
                ServiceController::class,
                ServiceFormController::class,
                InvoiceController::class,
                InvoiceFormController::class,
                QuoteController::class,
                QuoteFormController::class,
            ],
            'ucrm-icon--clients'
        );

        $builder->addLink(
            'Invoices',
            [
                'billing_index' => InvoiceController::class,
                'quote_index' => QuoteController::class,
            ],
            [
                BillingController::class,
                BillingInvoiceController::class,
                DraftController::class,
                BillingQuoteController::class,
            ],
            'ucrm-icon--invoices appGlobalSideNav__itemIcon--large'
        );

        $builder->addLink(
            'Payments',
            [
                'payment_index' => PaymentController::class,
                'refund_index' => RefundController::class,
            ],
            [
                PaymentController::class,
                RefundController::class,
            ],
            'ucrm-icon--payment appGlobalSideNav__itemIcon--smaller'
        );

        if (
            $this->permissionGrantedChecker->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY)
            || $this->permissionGrantedChecker->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL)
        ) {
            $builder->addLink(
                'Scheduling',
                [
                    'scheduling_timeline_index' => [
                        'controller' => TimelineController::class,
                        'permission' => Permission::GUEST,
                    ],
                ],
                [
                    TimelineController::class,
                    AgendaController::class,
                    JobController::class,
                ],
                'ucrm-icon--calendar-check appGlobalSideNav__itemIcon--small'
            );
        }

        $builder->addLink(
            'Ticketing',
            [
                'ticketing_index' => TicketController::class,
            ],
            [
                TicketController::class,
            ],
            'ucrm-icon--messages-question'
        );

        $network = $builder->addParent(
            'Network',
            'ucrm-icon--site appGlobalSideNav__itemIcon--small'
        );

        $builder->addChildLink(
            $network,
            'Sites',
            [
                'site_index' => SiteController::class,
            ],
            [
                SiteController::class,
            ]
        );

        $builder->addChildLink(
            $network,
            'Devices',
            [
                'device_index' => DeviceController::class,
            ],
            [
                DeviceController::class,
                DeviceInterfaceController::class,
            ]
        );

        $builder->addChildLink(
            $network,
            'Outages',
            [
                'outage_index' => OutageController::class,
            ],
            [
                OutageController::class,
            ]
        );

        $builder->addChildLink(
            $network,
            'Unknown devices',
            [
                'unknown_devices_index' => UnknownDevicesController::class,
            ],
            [
                UnknownDevicesController::class,
            ]
        );

        $builder->addChildLink(
            $network,
            'Network map',
            [
                'network_map_index' => NetworkMapController::class,
            ],
            [
                NetworkMapController::class,
            ]
        );

        $report = $builder->addParent(
            'Reports',
            'ucrm-icon--reports'
        );

        $builder->addChildLink(
            $report,
            'Data usage',
            [
                'report_data_usage_overview' => ReportDataUsageController::class,
            ],
            [
                ReportDataUsageController::class,
            ]
        );

        $builder->addChildLink(
            $report,
            'Billing',
            [
                'invoiced_revenue_index' => InvoicedRevenueController::class,
                'taxes_index' => TaxReportController::class,
            ],
            [
                InvoicedRevenueController::class,
                TaxReportController::class,
            ]
        );

        $system = $builder->addParent(
            'System',
            'ucrm-icon--settings'
        );

        $builder->addChildLink(
            $system,
            'Settings',
            [
                'setting_application_edit' => SettingApplicationController::class,
                'setting_mailer_edit' => SettingMailerController::class,
                'setting_outage_edit' => SettingOutageController::class,
                'setting_qos_edit' => SettingQosController::class,
                'setting_sync_edit' => SettingSyncController::class,
                'setting_netflow_edit' => SettingNetFlowController::class,
                'setting_logs_edit' => SettingLogsController::class,
                'setting_localization_edit' => SettingLocalizationController::class,
                'setting_oauth_edit' => SettingOAuthController::class,
                'setting_ticketing_edit' => SettingTicketingController::class,
                'setting_client_zone_edit' => SettingClientZoneController::class,
            ],
            [
                SettingApplicationController::class,
                SettingMailerController::class,
                SettingOutageController::class,
                SettingQosController::class,
                SettingSyncController::class,
                SettingNetFlowController::class,
                SettingLogsController::class,
                SettingLocalizationController::class,
                SettingOAuthController::class,
                SettingTicketingController::class,
                SettingClientZoneController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Organizations',
            [
                'organization_index' => OrganizationController::class,
            ],
            [
                OrganizationController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Service plans & Products',
            [
                'tariff_index' => TariffController::class,
                'product_index' => ProductController::class,
                'surcharge_index' => SurchargeController::class,
            ],
            [
                TariffController::class,
                ProductController::class,
                SurchargeController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Billing',
            [
                'setting_billing_edit' => SettingBillingController::class,
                'setting_suspend_edit' => SettingSuspendController::class,
                'setting_fees_edit' => SettingFeesController::class,
                'organization_setting_index' => OrganizationSettingController::class,
                'organization_bank_account_index' => OrganizationBankAccountController::class,
                'tax_index' => TaxController::class,
            ],
            [
                SettingBillingController::class,
                SettingSuspendController::class,
                SettingFeesController::class,
                OrganizationSettingController::class,
                OrganizationBankAccountController::class,
                TaxController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Customization',
            [
                'email_templates_index' => EmailTemplatesController::class,
                'suspension_templates_index' => SuspensionTemplatesController::class,
                'appearance_index' => AppearanceController::class,
                'invoice_template_index' => InvoiceTemplateController::class,
                'account_statement_template_index' => AccountStatementTemplateController::class,
                'quote_template_index' => QuoteTemplateController::class,
                'payment_receipt_template_index' => PaymentReceiptTemplateController::class,
                'notification_settings_index' => NotificationSettingsController::class,
                'client_zone_page_index' => ClientZonePageController::class,
                'proforma_invoice_template_index' => ProformaInvoiceTemplateController::class,
            ],
            [
                EmailTemplatesController::class,
                SuspensionTemplatesController::class,
                AppearanceController::class,
                InvoiceTemplateController::class,
                AccountStatementTemplateController::class,
                QuoteTemplateController::class,
                PaymentReceiptTemplateController::class,
                NotificationSettingsController::class,
                ClientZonePageController::class,
                ProformaInvoiceTemplateController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Tools',
            [
                'backup_index' => BackupController::class,
                'import_clients_index' => ClientImportController::class,
                'import_payments_index' => PaymentImportController::class,
                'certificate_index' => CertificateController::class,
                'webroot_index' => WebrootController::class,
                'download_index' => DownloadController::class,
                'fcc_reports_index' => FccReportsController::class,
                'updates_index' => UpdatesController::class,
                'mailing_index' => MailingController::class,
            ],
            [
                BackupController::class,
                ClientImportController::class,
                ClientImportCancelController::class,
                ClientImportMappingController::class,
                ClientImportPreviewController::class,
                ClientImportSaveController::class,
                ClientImportUploadController::class,
                PaymentImportController::class,
                CertificateController::class,
                WebrootController::class,
                DownloadController::class,
                FccReportsController::class,
                UpdatesController::class,
                MailingController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Users',
            [
                'user_index' => UserController::class,
                'user_group_index' => UserGroupController::class,
                'app_key_index' => AppKeyController::class,
            ],
            [
                UserController::class,
                UserGroupController::class,
                AppKeyController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Plugins',
            [
                'plugin_index' => PluginListController::class,
            ],
            [
                PluginListController::class,
                PluginController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Webhooks',
            [
                'webhook_endpoint_index' => WebhookEndpointController::class,
                'webhook_log_index' => WebhookLogController::class,
            ],
            [
                WebhookEndpointController::class,
                WebhookLogController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Logs',
            [
                'device_log_index' => DeviceLogController::class,
                'email_log_index' => EmailLogController::class,
                'entity_log_index' => EntityLogController::class,
            ],
            [
                DeviceLogController::class,
                EmailLogController::class,
                EntityLogController::class,
            ]
        );

        $builder->addChildLink(
            $system,
            'Other',
            [
                'vendor_index' => VendorController::class,
                'service_stop_reason_index' => ServiceStopReasonController::class,
                'custom_attribute_index' => CustomAttributeController::class,
                'client_tag_index' => ClientTagController::class,
                'contact_type_index' => ContactTypeController::class,
            ],
            [
                VendorController::class,
                ServiceStopReasonController::class,
                CustomAttributeController::class,
                ClientTagController::class,
                ContactTypeController::class,
            ]
        );

        return $builder;
    }
}
