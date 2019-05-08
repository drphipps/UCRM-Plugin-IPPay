<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Statistics;

class UcrmStatisticsData
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var string|null
     */
    public $lastLogin;

    /**
     * @var string|null
     */
    public $token;

    /**
     * @var string|null
     */
    public $uasInstallation;

    /**
     * @var mixed[]
     */
    public $counts = [
        'invoices' => null,
        'clients' => null,
        'leads' => null,
        'organizations' => null,
        'jobs' => null,
        'tickets' => null,
        'admins' => null,
    ];

    /**
     * @var mixed[]
     */
    public $paymentGateways = [
        'listActive' => null,
        'recurringEnabled' => null,
        'autopayEnabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $mailer = [
        'type' => null,
    ];

    /**
     * @var mixed[]
     */
    public $ticketing = [
        'enabled' => null,
        'imapEnabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $scheduling = [
        'googleCalendarSyncEnabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $invoicingSettings = [
        'pricingMode' => null,
        'periodType' => null,
        'periodTypeBackwardAverage' => null,
        'periodStartDay' => null,
        'automaticDraftApprovalEnabled' => null,
        'automaticDraftApprovalEnabledServiceAverage' => null,
        'customInvoiceTemplateUsed' => null,
        'generateProformaInvoicesEnabled' => null,
        'generateProformaInvoicesEnabledClientAverage' => null,
    ];

    /**
     * @var mixed[]
     */
    public $suspension = [
        'enabled' => null,
        'postponeEnabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $fees = [
        'lateFeeEnabled' => null,
        'setupFeeEnabled' => null,
        'earlyTerminationFeeEnabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $localization = [
        'systemLanguage' => null,
        'timezone' => null,
    ];

    /**
     * @var mixed[]
     */
    public $twoFactorAuthentication = [
        'enabled' => null,
    ];

    /**
     * @var mixed[]
     */
    public $appKeys = [
        'exist' => null,
        'lastUsedDate' => null,
        'mobileExist' => null,
        'mobileLastUsedDate' => null,
    ];

    /**
     * @var mixed[]
     */
    public $ssl = [
        'enabled' => null,
        'certType' => null,
    ];

    /**
     * @var mixed[]
     */
    public $general = [
        'sandboxEnabled' => null,
        'mapsProvider' => null,
        'errorReportingEnabled' => null,
        'hasInvoices' => null,
        'hasClients' => null,
    ];

    /**
     * @var mixed[] - list[string routeName => int count]
     */
    public $shortcuts = [
        'list' => null,
    ];

    /**
     * @var mixed[] - list[string pluginName => bool enabled]
     */
    public $plugins = [
        'list' => null,
    ];

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
