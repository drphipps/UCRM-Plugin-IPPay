<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GeneralRepository")
 */
class General
{
    public const ANET_LAST_BATCH_LIST = 'anet_last_batch_list';
    public const CRM_API_SEND_COUNT_CLIENTS = 'cmr_api_send_count_clients';
    public const CRM_API_SEND_COUNT_INVOICES = 'crm_api_send_count_invoices';
    public const CRM_API_SEND_COUNT_ORGANIZATIONS = 'crm_api_send_count_organizations';
    public const CRM_API_TOKEN = 'crm_api_token';
    public const CRM_INSTALLED_VERSION = 'crm_installed_version';
    public const INVOICE_TOTALS_MIGRATION_COMPLETE = 'invoice_totals_migration_complete';
    public const NETFLOW_LAST_AGGREGATION_TIMESTAMP = 'netflow_last_aggregation_timestamp';
    public const NETFLOW_NETWORK_AGGREGATION_DATE = 'netflow_network_aggregation_date';
    public const NETFLOW_SERVICE_AGGREGATION_DATE = 'netflow_service_aggregation_date';
    public const PING_NETWORK_LONG_TERM_AGGREGATION_DATE = 'ping_network_long_term_aggregation_date';
    public const PING_NETWORK_SHORT_TERM_AGGREGATION_DATE = 'ping_network_short_term_aggregation_date';
    public const PING_SERVICE_LONG_TERM_AGGREGATION_DATE = 'ping_service_long_term_aggregation_date';
    public const PING_SERVICE_SHORT_TERM_AGGREGATION_DATE = 'ping_service_short_term_aggregation_date';
    public const SANDBOX_MODE = 'sandbox_mode';
    public const WIRELESS_STATISTICS_AGGREGATION_DATE = 'wireless_statistics_aggregation_date';
    public const WIRELESS_STATISTICS_SERVICE_AGGREGATION_DATE = 'wireless_statistics_service_aggregation_date';
    public const SUSPEND_SYNCHRONIZED = 'suspend_synchronized';
    public const UAS_INSTALLATION = 'uas_installation';

    public const MAILER_ANTIFLOOD_COUNTER = 'mailer_antiflood_counter';
    public const MAILER_ANTIFLOOD_TIMESTAMP = 'mailer_antiflood_timestamp';
    public const MAILER_THROTTLER_COUNTER = 'mailer_throttler_counter';
    public const MAILER_THROTTLER_TIMESTAMP = 'mailer_throttler_timestamp';

    public const TICKETING_IMAP_LAST_EMAIL_UID = 'ticketing_imap_last_email_uid';
    public const TICKETING_IMAP_SETTING_TIMESTAMP = 'ticketing_imap_setting_timestamp';

    public const APPEARANCE_FAVICON = 'appearance_favicon';
    public const APPEARANCE_LOGIN_BANNER = 'appearance_login_banner';

    public const ONBOARDING_HOMEPAGE_FINISHED = 'onboarding_homepage_finished';
    public const ONBOARDING_HOMEPAGE_BILLING = 'onboarding_homepage_billing';
    public const ONBOARDING_HOMEPAGE_SYSTEM = 'onboarding_homepage_system';
    public const ONBOARDING_HOMEPAGE_MAILER = 'onboarding_homepage_mailer';
    public const ONBOARDING_HOMEPAGE_MAILER_VIA_WIZARD = 'onboarding_homepage_mailer_via_wizard';

    public const WIZARD_ACCOUNT_DONE = 'wizard_account_done';

    public const DROPBOX_SYNC_TIMESTAMP = 'dropbox_sync_timestamp';

    public const DEMO_MIGRATION_SHIFT = 'demo_migration_shift';

    /**
     * @var int
     *
     * @ORM\Column(name="general_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    protected $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="value", type="string", length=500, nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     *
     * @return General
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed|null $value
     *
     * @return General
     */
    public function setValue($value)
    {
        $this->value = null !== $value ? (string) $value : null;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
