<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OptionRepository")
 */
class Option implements LoggableInterface, ParentLoggableInterface
{
    public const APP_LOCALE = 'APP_LOCALE';
    public const APP_TIMEZONE = 'APP_TIMEZONE';
    public const BACKUP_INCLUDE_INVOICE_TEMPLATES = 'BACKUP_INCLUDE_INVOICE_TEMPLATES';
    public const BACKUP_INCLUDE_QUOTE_TEMPLATES = 'BACKUP_INCLUDE_QUOTE_TEMPLATES';
    public const BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES = 'BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES';
    public const BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES = 'BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES';
    public const BACKUP_INCLUDE_SSL_CERTIFICATES = 'BACKUP_INCLUDE_SSL_CERTIFICATES';
    public const BACKUP_INCLUDE_DOWNLOADS = 'BACKUP_INCLUDE_DOWNLOADS';
    public const BACKUP_INCLUDE_MEDIA = 'BACKUP_INCLUDE_MEDIA';
    public const BACKUP_INCLUDE_WEBROOT = 'BACKUP_INCLUDE_WEBROOT';
    public const BACKUP_INCLUDE_DOCUMENTS = 'BACKUP_INCLUDE_DOCUMENTS';
    public const BACKUP_INCLUDE_PLUGINS = 'BACKUP_INCLUDE_PLUGINS';
    public const BACKUP_INCLUDE_TICKET_ATTACHMENTS = 'BACKUP_INCLUDE_TICKET_ATTACHMENTS';
    public const BACKUP_INCLUDE_JOB_ATTACHMENTS = 'BACKUP_INCLUDE_JOB_ATTACHMENTS';
    public const BACKUP_REMOTE_DROPBOX = 'BACKUP_REMOTE_DROPBOX';
    public const BACKUP_REMOTE_DROPBOX_TOKEN = 'BACKUP_REMOTE_DROPBOX_TOKEN';
    public const BACKUP_LIFETIME_COUNT = 'BACKUP_LIFETIME_COUNT';
    public const BACKUP_FILENAME_PREFIX = 'BACKUP_FILENAME_PREFIX';
    public const BALANCE_STYLE = 'BALANCE_STYLE';
    public const BILLING_CYCLE_TYPE = 'BILLING_CYCLE_TYPE';
    public const CLIENT_ID_TYPE = 'CLIENT_ID_TYPE';
    public const CLIENT_ZONE_REACTIVATION = 'CLIENT_ZONE_REACTIVATION';
    public const CLIENT_ZONE_SCHEDULING = 'CLIENT_ZONE_SCHEDULING';
    public const CLIENT_ZONE_PAYMENT_DETAILS = 'CLIENT_ZONE_PAYMENT_DETAILS';
    public const CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE = 'CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE';
    public const CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION = 'CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION';
    public const DISCOUNT_INVOICE_LABEL = 'DISCOUNT_INVOICE_LABEL';
    public const EARLY_TERMINATION_FEE_INVOICE_LABEL = 'EARLY_TERMINATION_FEE_INVOICE_LABEL';
    public const EARLY_TERMINATION_FEE_TAXABLE = 'EARLY_TERMINATION_FEE_TAXABLE';
    public const EARLY_TERMINATION_FEE_TAX_ID = 'EARLY_TERMINATION_FEE_TAX_ID';
    public const ERROR_REPORTING = 'ERROR_REPORTING';
    public const FCC_ALWAYS_USE_GPS = 'FCC_ALWAYS_USE_GPS';
    public const FORMAT_DATE_DEFAULT = 'FORMAT_DATE_DEFAULT';
    public const FORMAT_DATE_ALTERNATIVE = 'FORMAT_DATE_ALTERNATIVE';
    public const FORMAT_DECIMAL_SEPARATOR = 'FORMAT_DECIMAL_SEPARATOR';
    public const FORMAT_THOUSANDS_SEPARATOR = 'FORMAT_THOUSANDS_SEPARATOR';
    public const FORMAT_TIME = 'FORMAT_TIME';
    public const GENERATE_PROFORMA_INVOICES = 'GENERATE_PROFORMA_INVOICES';
    public const GOOGLE_API_KEY = 'GOOGLE_API_KEY';
    public const GOOGLE_OAUTH_SECRET = 'GOOGLE_OAUTH_SECRET';
    public const HEADER_NOTIFICATIONS_LIFETIME = 'HEADER_NOTIFICATIONS_LIFETIME';
    public const INVOICE_ITEM_ROUNDING = 'INVOICE_ITEM_ROUNDING';
    public const INVOICE_PERIOD_START_DAY = 'INVOICE_PERIOD_START_DAY';
    public const INVOICE_TAX_ROUNDING = 'INVOICE_TAX_ROUNDING';
    public const INVOICE_TIME_HOUR = 'INVOICE_TIME_HOUR';
    public const INVOICING_PERIOD_TYPE = 'INVOICING_PERIOD_TYPE';
    public const LATE_FEE_ACTIVE = 'LATE_FEE_ACTIVE';
    public const LATE_FEE_DELAY_DAYS = 'LATE_FEE_DELAY_DAYS';
    public const LATE_FEE_INVOICE_LABEL = 'LATE_FEE_INVOICE_LABEL';
    public const LATE_FEE_PRICE = 'LATE_FEE_PRICE';
    public const LATE_FEE_PRICE_TYPE = 'LATE_FEE_PRICE_TYPE';
    public const LATE_FEE_TAXABLE = 'LATE_FEE_TAXABLE';
    public const LATE_FEE_TAX_ID = 'LATE_FEE_TAX_ID';
    public const LOG_LIFETIME_DEVICE = 'LOG_LIFETIME_DEVICE';
    public const LOG_LIFETIME_EMAIL = 'LOG_LIFETIME_EMAIL';
    public const LOG_LIFETIME_ENTITY = 'LOG_LIFETIME_ENTITY';
    public const LOG_LIFETIME_SERVICE_DEVICE = 'LOG_LIFETIME_SERVICE_DEVICE';
    public const MAILER_VERIFY_SSL_CERTIFICATES = 'MAILER_VERIFY_SSL_CERTIFICATES';
    public const MAILER_ANTIFLOOD_LIMIT_COUNT = 'MAILER_ANTIFLOOD_LIMIT_COUNT';
    public const MAILER_ANTIFLOOD_SLEEP_TIME = 'MAILER_ANTIFLOOD_SLEEP_TIME';
    public const MAILER_AUTH_MODE = 'MAILER_AUTH_MODE';
    public const MAILER_ENCRYPTION = 'MAILER_ENCRYPTION';
    public const MAILER_HOST = 'MAILER_HOST';
    public const MAILER_PASSWORD = 'MAILER_PASSWORD';
    public const MAILER_PORT = 'MAILER_PORT';
    public const MAILER_SENDER_ADDRESS = 'MAILER_SENDER_ADDRESS';
    public const MAILER_THROTTLER_LIMIT_COUNT = 'MAILER_THROTTLER_LIMIT_COUNT';
    public const MAILER_THROTTLER_LIMIT_TIME = 'MAILER_THROTTLER_LIMIT_TIME';
    public const MAILER_TRANSPORT = 'MAILER_TRANSPORT';
    public const MAILER_USERNAME = 'MAILER_USERNAME';
    public const MAPBOX_TOKEN = 'MAPBOX_TOKEN';
    public const NETFLOW_AGGREGATION_FREQUENCY = 'NETFLOW_AGGREGATION_FREQUENCY';
    public const NETFLOW_MINIMUM_UNKNOWN_TRAFFIC = 'NETFLOW_MINIMUM_UNKNOWN_TRAFFIC';
    public const NOTIFICATION_PING_DOWN = 'NOTIFICATION_PING_DOWN';
    public const NOTIFICATION_PING_REPAIRED = 'NOTIFICATION_PING_REPAIRED';
    public const NOTIFICATION_PING_UNREACHABLE = 'NOTIFICATION_PING_UNREACHABLE';
    public const NOTIFICATION_PING_USER = 'NOTIFICATION_PING_USER';
    public const PDF_PAGE_SIZE = 'PDF_PAGE_SIZE';
    public const PDF_PAGE_SIZE_EXPORT = 'PDF_PAGE_SIZE_EXPORT';
    public const PDF_PAGE_SIZE_INVOICE = 'PDF_PAGE_SIZE_INVOICE';
    public const PDF_PAGE_SIZE_PAYMENT_RECEIPT = 'PDF_PAGE_SIZE_PAYMENT_RECEIPT';
    public const PING_OUTAGE_THRESHOLD = 'PING_OUTAGE_THRESHOLD';
    public const PRICING_MODE = 'PRICING_MODE';
    public const PRICING_MULTIPLE_TAXES = 'PRICING_MULTIPLE_TAXES';
    public const PRICING_TAX_COEFFICIENT_PRECISION = 'PRICING_TAX_COEFFICIENT_PRECISION';
    public const QOS_DESTINATION = 'QOS_DESTINATION';
    public const QOS_ENABLED = 'QOS_ENABLED';
    public const QOS_INTERFACE_AIR_OS = 'QOS_INTERFACE_AIR_OS';
    public const QOS_SYNC_TYPE = 'QOS_SYNC_TYPE';
    public const SUBSCRIPTIONS_ENABLED_CUSTOM = 'SUBSCRIPTIONS_ENABLED_CUSTOM';
    public const SUBSCRIPTIONS_ENABLED_LINKED = 'SUBSCRIPTIONS_ENABLED_LINKED';
    public const SEND_INVOICE_BY_EMAIL = 'SEND_INVOICE_BY_EMAIL';
    public const SEND_INVOICE_BY_POST = 'SEND_INVOICE_BY_POST';
    public const SERVER_FQDN = 'SERVER_FQDN';
    public const SERVER_IP = 'SERVER_IP';
    public const SERVER_PORT = 'SERVER_PORT';
    public const SERVER_SUSPEND_PORT = 'SERVER_SUSPEND_PORT';
    public const SERVICE_INVOICING_DAY_ADJUSTMENT = 'SERVICE_INVOICING_DAY_ADJUSTMENT';
    public const SETUP_FEE_INVOICE_LABEL = 'SETUP_FEE_INVOICE_LABEL';
    public const SETUP_FEE_TAXABLE = 'SETUP_FEE_TAXABLE';
    public const SETUP_FEE_TAX_ID = 'SETUP_FEE_TAX_ID';
    public const SITE_NAME = 'SITE_NAME';
    public const SEND_ANONYMOUS_STATISTICS = 'SEND_ANONYMOUS_STATISTICS';
    public const STOP_INVOICING = 'STOP_INVOICING';
    public const STOP_SERVICE_DUE = 'STOP_SERVICE_DUE';
    public const STOP_SERVICE_DUE_DAYS = 'STOP_SERVICE_DUE_DAYS';
    public const SUPPORT_EMAIL_ADDRESS = 'SUPPORT_EMAIL_ADDRESS';
    public const SUSPEND_ENABLED = 'SUSPEND_ENABLED';
    public const SUSPENSION_ENABLE_POSTPONE = 'SUSPENSION_ENABLE_POSTPONE';
    public const SUSPENSION_MINIMUM_UNPAID_AMOUNT = 'SUSPENSION_MINIMUM_UNPAID_AMOUNT';
    public const SYNC_ENABLED = 'SYNC_ENABLED';
    public const SYNC_FREQUENCY = 'SYNC_FREQUENCY';
    public const TICKETING_ENABLED = 'TICKETING_ENABLED';
    public const TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED = 'TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED';
    public const TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT = 'TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT';
    public const UPDATE_CHANNEL = 'UPDATE_CHANNEL';

    // notification options
    public const NOTIFICATION_CREATED_DRAFTS_BY_EMAIL = 'NOTIFICATION_CREATED_DRAFTS_BY_EMAIL';
    public const NOTIFICATION_CREATED_DRAFTS_IN_HEADER = 'NOTIFICATION_CREATED_DRAFTS_IN_HEADER';
    public const NOTIFICATION_CREATED_INVOICES_BY_EMAIL = 'NOTIFICATION_CREATED_INVOICES_BY_EMAIL';
    public const NOTIFICATION_CREATED_INVOICES_IN_HEADER = 'NOTIFICATION_CREATED_INVOICES_IN_HEADER';
    public const NOTIFICATION_EMAIL_ADDRESS = 'NOTIFICATION_EMAIL_ADDRESS';
    public const NOTIFICATION_INVOICE_NEAR_DUE = 'NOTIFICATION_INVOICE_NEAR_DUE';
    public const NOTIFICATION_INVOICE_NEAR_DUE_DAYS = 'NOTIFICATION_INVOICE_NEAR_DUE_DAYS';
    public const NOTIFICATION_INVOICE_NEW = 'NOTIFICATION_INVOICE_NEW';
    public const NOTIFICATION_INVOICE_OVERDUE = 'NOTIFICATION_INVOICE_OVERDUE';
    public const NOTIFICATION_SERVICE_SUSPENDED = 'NOTIFICATION_SERVICE_SUSPENDED';
    public const NOTIFICATION_SERVICE_SUSPENSION_POSTPONED = 'NOTIFICATION_SERVICE_SUSPENSION_POSTPONED';
    public const NOTIFICATION_SUBSCRIPTION_CANCELLED = 'NOTIFICATION_SUBSCRIPTION_CANCELLED';
    public const NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED = 'NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED';
    public const SEND_INVOICE_WITH_ZERO_BALANCE = 'SEND_INVOICE_WITH_ZERO_BALANCE';
    public const NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL = 'NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL';
    public const NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER = 'NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER';
    public const NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL = 'NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL';
    public const NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER = 'NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER';
    public const NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL = 'NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL';
    public const NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL = 'NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL';
    public const NOTIFICATION_TICKET_USER_CHANGED_STATUS = 'NOTIFICATION_TICKET_USER_CHANGED_STATUS';

    public const SEND_PAYMENT_RECEIPTS = 'SEND_PAYMENT_RECEIPTS';

    public const TYPE_CASTS = [
        self::APP_LOCALE => 'string',
        self::APP_TIMEZONE => 'string',
        self::BACKUP_INCLUDE_INVOICE_TEMPLATES => 'bool',
        self::BACKUP_INCLUDE_QUOTE_TEMPLATES => 'bool',
        self::BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES => 'bool',
        self::BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES => 'bool',
        self::BACKUP_INCLUDE_SSL_CERTIFICATES => 'bool',
        self::BACKUP_INCLUDE_DOWNLOADS => 'bool',
        self::BACKUP_INCLUDE_MEDIA => 'bool',
        self::BACKUP_INCLUDE_WEBROOT => 'bool',
        self::BACKUP_INCLUDE_DOCUMENTS => 'bool',
        self::BACKUP_INCLUDE_PLUGINS => 'bool',
        self::BACKUP_INCLUDE_TICKET_ATTACHMENTS => 'bool',
        self::BACKUP_INCLUDE_JOB_ATTACHMENTS => 'bool',
        self::BACKUP_REMOTE_DROPBOX => 'bool',
        self::BACKUP_REMOTE_DROPBOX_TOKEN => 'string',
        self::BACKUP_LIFETIME_COUNT => 'int',
        self::BACKUP_FILENAME_PREFIX => 'string',
        self::BALANCE_STYLE => 'string',
        self::BILLING_CYCLE_TYPE => 'int',
        self::CLIENT_ID_TYPE => 'int',
        self::CLIENT_ZONE_REACTIVATION => 'bool',
        self::CLIENT_ZONE_SCHEDULING => 'bool',
        self::CLIENT_ZONE_PAYMENT_DETAILS => 'bool',
        self::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE => 'bool',
        self::CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION => 'bool',
        self::DISCOUNT_INVOICE_LABEL => 'string',
        self::EARLY_TERMINATION_FEE_INVOICE_LABEL => 'string',
        self::EARLY_TERMINATION_FEE_TAXABLE => 'bool',
        self::EARLY_TERMINATION_FEE_TAX_ID => 'int',
        self::ERROR_REPORTING => 'bool',
        self::FCC_ALWAYS_USE_GPS => 'bool',
        self::FORMAT_DATE_DEFAULT => 'int',
        self::FORMAT_DATE_ALTERNATIVE => 'int',
        self::FORMAT_DECIMAL_SEPARATOR => 'string',
        self::FORMAT_THOUSANDS_SEPARATOR => 'string',
        self::FORMAT_TIME => 'int',
        self::GOOGLE_API_KEY => 'string',
        self::GOOGLE_OAUTH_SECRET => 'string',
        self::HEADER_NOTIFICATIONS_LIFETIME => 'int',
        self::INVOICE_ITEM_ROUNDING => 'int',
        self::INVOICE_PERIOD_START_DAY => 'int',
        self::INVOICE_TAX_ROUNDING => 'int',
        self::INVOICE_TIME_HOUR => 'int',
        self::INVOICING_PERIOD_TYPE => 'int',
        self::LATE_FEE_ACTIVE => 'bool',
        self::LATE_FEE_DELAY_DAYS => 'int',
        self::LATE_FEE_INVOICE_LABEL => 'string',
        self::LATE_FEE_PRICE => 'float',
        self::LATE_FEE_PRICE_TYPE => 'int',
        self::LATE_FEE_TAXABLE => 'bool',
        self::LATE_FEE_TAX_ID => 'int',
        self::LOG_LIFETIME_DEVICE => 'int',
        self::LOG_LIFETIME_EMAIL => 'int',
        self::LOG_LIFETIME_ENTITY => 'int',
        self::LOG_LIFETIME_SERVICE_DEVICE => 'int',
        self::MAILER_VERIFY_SSL_CERTIFICATES => 'bool',
        self::MAILER_ANTIFLOOD_LIMIT_COUNT => 'int',
        self::MAILER_ANTIFLOOD_SLEEP_TIME => 'int',
        self::MAILER_AUTH_MODE => 'string',
        self::MAILER_ENCRYPTION => 'string',
        self::MAILER_HOST => 'string',
        self::MAILER_PASSWORD => 'string',
        self::MAILER_PORT => 'int',
        self::MAILER_SENDER_ADDRESS => 'string',
        self::MAILER_THROTTLER_LIMIT_COUNT => 'int',
        self::MAILER_THROTTLER_LIMIT_TIME => 'int',
        self::MAILER_TRANSPORT => 'string',
        self::MAILER_USERNAME => 'string',
        self::MAPBOX_TOKEN => 'string',
        self::NETFLOW_AGGREGATION_FREQUENCY => 'int',
        self::NETFLOW_MINIMUM_UNKNOWN_TRAFFIC => 'int',
        self::NOTIFICATION_CREATED_DRAFTS_BY_EMAIL => 'bool',
        self::NOTIFICATION_CREATED_DRAFTS_IN_HEADER => 'bool',
        self::NOTIFICATION_CREATED_INVOICES_BY_EMAIL => 'bool',
        self::NOTIFICATION_CREATED_INVOICES_IN_HEADER => 'bool',
        self::NOTIFICATION_EMAIL_ADDRESS => 'string',
        self::NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL => 'bool',
        self::NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER => 'bool',
        self::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL => 'bool',
        self::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER => 'bool',
        self::NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL => 'bool',
        self::NOTIFICATION_TICKET_USER_CHANGED_STATUS => 'bool',
        self::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL => 'bool',
        self::NOTIFICATION_INVOICE_NEAR_DUE => 'bool',
        self::NOTIFICATION_INVOICE_NEAR_DUE_DAYS => 'int',
        self::NOTIFICATION_INVOICE_NEW => 'bool',
        self::NOTIFICATION_INVOICE_OVERDUE => 'bool',
        self::NOTIFICATION_PING_DOWN => 'bool',
        self::NOTIFICATION_PING_REPAIRED => 'bool',
        self::NOTIFICATION_PING_UNREACHABLE => 'bool',
        self::NOTIFICATION_PING_USER => 'int',
        self::NOTIFICATION_SERVICE_SUSPENDED => 'bool',
        self::NOTIFICATION_SERVICE_SUSPENSION_POSTPONED => 'bool',
        self::NOTIFICATION_SUBSCRIPTION_CANCELLED => 'bool',
        self::NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED => 'bool',
        self::PDF_PAGE_SIZE_EXPORT => 'string',
        self::PDF_PAGE_SIZE_INVOICE => 'string',
        self::PDF_PAGE_SIZE_PAYMENT_RECEIPT => 'string',
        self::PING_OUTAGE_THRESHOLD => 'int',
        self::PRICING_MODE => 'int',
        self::PRICING_MULTIPLE_TAXES => 'bool',
        self::PRICING_TAX_COEFFICIENT_PRECISION => 'int',
        self::GENERATE_PROFORMA_INVOICES => 'bool',
        self::QOS_DESTINATION => 'string',
        self::QOS_ENABLED => 'bool',
        self::QOS_INTERFACE_AIR_OS => 'int',
        self::QOS_SYNC_TYPE => 'string',
        self::SUBSCRIPTIONS_ENABLED_CUSTOM => 'bool',
        self::SUBSCRIPTIONS_ENABLED_LINKED => 'bool',
        self::SEND_INVOICE_BY_EMAIL => 'bool',
        self::SEND_INVOICE_BY_POST => 'bool',
        self::SEND_INVOICE_WITH_ZERO_BALANCE => 'bool',
        self::SEND_PAYMENT_RECEIPTS => 'bool',
        self::SERVER_FQDN => 'string',
        self::SERVER_IP => 'string',
        self::SERVER_PORT => 'int',
        self::SERVER_SUSPEND_PORT => 'int',
        self::SERVICE_INVOICING_DAY_ADJUSTMENT => 'int',
        self::SETUP_FEE_INVOICE_LABEL => 'string',
        self::SETUP_FEE_TAXABLE => 'bool',
        self::SETUP_FEE_TAX_ID => 'int',
        self::SITE_NAME => 'string',
        self::SEND_ANONYMOUS_STATISTICS => 'bool',
        self::STOP_INVOICING => 'bool',
        self::STOP_SERVICE_DUE => 'bool',
        self::STOP_SERVICE_DUE_DAYS => 'int',
        self::SUPPORT_EMAIL_ADDRESS => 'string',
        self::SUSPEND_ENABLED => 'bool',
        self::SUSPENSION_ENABLE_POSTPONE => 'bool',
        self::SUSPENSION_MINIMUM_UNPAID_AMOUNT => 'float',
        self::SYNC_ENABLED => 'bool',
        self::SYNC_FREQUENCY => 'int',
        self::TICKETING_ENABLED => 'bool',
        self::TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED => 'bool',
        self::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT => 'float',
        self::UPDATE_CHANNEL => 'string',
    ];

    public const NAMES = [
        self::APP_LOCALE => 'Language',
        self::APP_TIMEZONE => 'Timezone',
        self::BACKUP_INCLUDE_INVOICE_TEMPLATES => 'Include invoice templates',
        self::BACKUP_INCLUDE_QUOTE_TEMPLATES => 'Include quote templates',
        self::BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES => 'Include account statement templates',
        self::BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES => 'Include receipt templates',
        self::BACKUP_INCLUDE_SSL_CERTIFICATES => 'Include SSL certificates',
        self::BACKUP_INCLUDE_DOWNLOADS => 'Include downloads (e.g. generated billing report)',
        self::BACKUP_INCLUDE_MEDIA => 'Include media (e.g. organization logo and stamp)',
        self::BACKUP_INCLUDE_WEBROOT => 'Include custom webroot files',
        self::BACKUP_INCLUDE_DOCUMENTS => 'Include client documents',
        self::BACKUP_INCLUDE_PLUGINS => 'Include plugins',
        self::BACKUP_INCLUDE_TICKET_ATTACHMENTS => 'Include ticket attachments',
        self::BACKUP_INCLUDE_JOB_ATTACHMENTS => 'Include job attachments',
        self::BACKUP_REMOTE_DROPBOX => 'Synchronize with Dropbox',
        self::BACKUP_REMOTE_DROPBOX_TOKEN => 'Dropbox Access Token',
        self::BACKUP_LIFETIME_COUNT => 'How many backup files to keep at maximum',
        self::BACKUP_FILENAME_PREFIX => 'Backup filename prefix',
        self::BALANCE_STYLE => 'Positive or negative sign for client\'s balance',
        self::CLIENT_ID_TYPE => 'Displayed client ID',
        self::CLIENT_ZONE_REACTIVATION => 'Enable service reactivation',
        self::CLIENT_ZONE_SCHEDULING => 'Show jobs',
        self::CLIENT_ZONE_PAYMENT_DETAILS => 'Show payment details',
        self::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE => 'Enable change of payment amount',
        self::CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION => 'Show service plan shaping information',
        self::BILLING_CYCLE_TYPE => 'Billing cycle',
        self::DISCOUNT_INVOICE_LABEL => 'Discount invoice label',
        self::EARLY_TERMINATION_FEE_INVOICE_LABEL => 'Early termination fee invoice label',
        self::EARLY_TERMINATION_FEE_TAXABLE => 'Early termination fee taxable',
        self::EARLY_TERMINATION_FEE_TAX_ID => 'Early termination fee tax',
        self::ERROR_REPORTING => 'Error reporting',
        self::FCC_ALWAYS_USE_GPS => 'Always use GPS instead of address',
        self::FORMAT_DATE_DEFAULT => 'Default date format',
        self::FORMAT_DATE_ALTERNATIVE => 'Alternative date format',
        self::FORMAT_DECIMAL_SEPARATOR => 'Decimal separator symbol',
        self::FORMAT_THOUSANDS_SEPARATOR => 'Thousands separator symbol',
        self::FORMAT_TIME => 'Time format',
        self::GOOGLE_API_KEY => 'Google API key',
        self::GOOGLE_OAUTH_SECRET => 'Google OAuth secret',
        self::HEADER_NOTIFICATIONS_LIFETIME => 'Notifications',
        self::INVOICE_ITEM_ROUNDING => 'Invoice rounding',
        self::INVOICE_PERIOD_START_DAY => 'Default period start day',
        self::INVOICE_TAX_ROUNDING => 'Tax rounding',
        self::INVOICE_TIME_HOUR => 'Hour when recurring invoices will be generated',
        self::INVOICING_PERIOD_TYPE => 'Billing period type',
        self::LATE_FEE_ACTIVE => 'Late fee active',
        self::LATE_FEE_DELAY_DAYS => 'Late fee delay',
        self::LATE_FEE_INVOICE_LABEL => 'Late fee invoice label',
        self::LATE_FEE_PRICE => 'Late fee price',
        self::LATE_FEE_PRICE_TYPE => 'Late fee price type',
        self::LATE_FEE_TAXABLE => 'Late fee taxable',
        self::LATE_FEE_TAX_ID => 'Late fee tax',
        self::LOG_LIFETIME_DEVICE => 'Device logs',
        self::LOG_LIFETIME_EMAIL => 'Email logs',
        self::LOG_LIFETIME_ENTITY => 'System logs',
        self::LOG_LIFETIME_SERVICE_DEVICE => 'Service device logs',
        self::MAILER_VERIFY_SSL_CERTIFICATES => 'Verify SSL certificate',
        self::MAILER_ANTIFLOOD_LIMIT_COUNT => 'Mailer Antiflood count limit',
        self::MAILER_ANTIFLOOD_SLEEP_TIME => 'Mailer Antiflood sleep time',
        self::MAILER_AUTH_MODE => 'Use authentication',
        self::MAILER_ENCRYPTION => 'Encryption',
        self::MAILER_HOST => 'Host',
        self::MAILER_PASSWORD => 'Password',
        self::MAILER_PORT => 'Port',
        self::MAILER_SENDER_ADDRESS => 'Sender address',
        self::MAILER_THROTTLER_LIMIT_COUNT => 'Mailer Throttler count limit',
        self::MAILER_THROTTLER_LIMIT_TIME => 'Mailer Throttler time limit',
        self::MAILER_TRANSPORT => 'Transport type',
        self::MAILER_USERNAME => 'Username',
        self::MAPBOX_TOKEN => 'MapBox.com Token',
        self::NETFLOW_AGGREGATION_FREQUENCY => 'NetFlow graphs refresh interval',
        self::NETFLOW_MINIMUM_UNKNOWN_TRAFFIC => 'NetFlow minimum unknown traffic',
        self::NOTIFICATION_CREATED_DRAFTS_BY_EMAIL => 'Send by email',
        self::NOTIFICATION_CREATED_DRAFTS_IN_HEADER => 'Header notification',
        self::NOTIFICATION_CREATED_INVOICES_BY_EMAIL => 'Send by email',
        self::NOTIFICATION_CREATED_INVOICES_IN_HEADER => 'Header notification',
        self::NOTIFICATION_EMAIL_ADDRESS => 'System notification address',
        self::NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL => 'Send by email',
        self::NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER => 'Header notification',
        self::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL => 'Send by email',
        self::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER => 'Header notification',
        self::NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL => 'Ticket commented',
        self::NOTIFICATION_TICKET_USER_CHANGED_STATUS => 'Ticket changed status',
        self::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL => 'Ticket created by admin',
        self::NOTIFICATION_INVOICE_NEAR_DUE => 'Send notifications for invoices near due date',
        self::NOTIFICATION_INVOICE_NEAR_DUE_DAYS => 'How many days before the due date send the notification',
        self::NOTIFICATION_INVOICE_NEW => 'New invoice',
        self::NOTIFICATION_INVOICE_OVERDUE => 'Invoice overdue',
        self::NOTIFICATION_PING_DOWN => 'DOWN notifications',
        self::NOTIFICATION_PING_REPAIRED => 'REPAIRED notifications',
        self::NOTIFICATION_PING_UNREACHABLE => 'UNREACHABLE notifications',
        self::NOTIFICATION_PING_USER => 'Send notifications to',
        self::NOTIFICATION_SERVICE_SUSPENDED => 'Service suspended',
        self::NOTIFICATION_SERVICE_SUSPENSION_POSTPONED => 'Service suspension postponed',
        self::NOTIFICATION_SUBSCRIPTION_CANCELLED => 'Subscription cancelled',
        self::NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED => 'Subscription amount changed',
        self::PDF_PAGE_SIZE_EXPORT => 'Export page size',
        self::PDF_PAGE_SIZE_INVOICE => 'Invoice page size',
        self::PDF_PAGE_SIZE_PAYMENT_RECEIPT => 'Payment receipt page size',
        self::PING_OUTAGE_THRESHOLD => 'Outage threshold (packet loss %)',
        self::PRICING_MODE => 'Pricing mode',
        self::PRICING_MULTIPLE_TAXES => 'Multiple taxes',
        self::PRICING_TAX_COEFFICIENT_PRECISION => 'Decimal places for tax coefficient',
        self::GENERATE_PROFORMA_INVOICES => 'Generate proforma invoices',
        self::QOS_DESTINATION => 'Set up QoS on',
        self::QOS_ENABLED => 'QoS enabled',
        self::QOS_INTERFACE_AIR_OS => 'QoS on AirOS',
        self::QOS_SYNC_TYPE => 'QoS sync type',
        self::SUBSCRIPTIONS_ENABLED_CUSTOM => 'Custom subscriptions',
        self::SUBSCRIPTIONS_ENABLED_LINKED => 'Linked subscriptions',
        self::SEND_INVOICE_BY_EMAIL => 'Approve and send emails automatically',
        self::SEND_INVOICE_BY_POST => 'Send invoice by post',
        self::SEND_INVOICE_WITH_ZERO_BALANCE => 'Send invoices with zero balance',
        self::SEND_PAYMENT_RECEIPTS => 'Send payment receipts automatically',
        self::SERVER_FQDN => 'Server domain name',
        self::SERVER_IP => 'Server IP',
        self::SERVER_PORT => 'Server port',
        self::SERVER_SUSPEND_PORT => 'Server suspend port',
        self::SERVICE_INVOICING_DAY_ADJUSTMENT => 'Create invoice X days in advance',
        self::SETUP_FEE_INVOICE_LABEL => 'Setup fee invoice label',
        self::SETUP_FEE_TAXABLE => 'Setup fee taxable',
        self::SETUP_FEE_TAX_ID => 'Setup fee tax',
        self::SITE_NAME => 'Site name',
        self::SEND_ANONYMOUS_STATISTICS => 'Send anonymous statistics',
        self::STOP_INVOICING => 'Stop invoicing for suspended services',
        self::STOP_SERVICE_DUE => 'Suspend services if payment overdue',
        self::STOP_SERVICE_DUE_DAYS => 'Suspension delay',
        self::SUPPORT_EMAIL_ADDRESS => 'Support email address',
        self::SUSPEND_ENABLED => 'Suspend feature',
        self::SUSPENSION_ENABLE_POSTPONE => 'Enable suspension postponing',
        self::SUSPENSION_MINIMUM_UNPAID_AMOUNT => 'Minimum unpaid amount',
        self::SYNC_ENABLED => 'Device sync',
        self::SYNC_FREQUENCY => 'Sync frequency',
        self::TICKETING_ENABLED => 'Enable ticketing',
        self::TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED => 'Enable automatic reply',
        self::TICKETING_IMAP_ATTACHMENT_FILESIZE_IMPORT_LIMIT => 'Attachment file size import limit',
        self::UPDATE_CHANNEL => 'Update channel',
    ];

    public const MAILER_TRANSPORT_SMTP = 'smtp';
    public const MAILER_TRANSPORT_GMAIL = 'gmail';
    public const MAILER_TRANSPORTS = [
        self::MAILER_TRANSPORT_SMTP => 'SMTP',
        self::MAILER_TRANSPORT_GMAIL => 'Gmail',
    ];

    public const MAILER_ENCRYPTION_NONE = '';
    public const MAILER_ENCRYPTION_SSL = 'ssl';
    public const MAILER_ENCRYPTION_TLS = 'tls';
    public const MAILER_ENCRYPTIONS = [
        self::MAILER_ENCRYPTION_NONE => 'None',
        self::MAILER_ENCRYPTION_SSL => 'SSL',
        self::MAILER_ENCRYPTION_TLS => 'TLS',
    ];

    public const MAILER_AUTH_MODE_NONE = '';
    public const MAILER_AUTH_MODE_PLAIN = 'plain';
    public const MAILER_AUTH_MODE_LOGIN = 'login';
    public const MAILER_AUTH_MODE_CRAM_MD5 = 'cram-md5';
    public const MAILER_AUTH_MODS = [
        self::MAILER_AUTH_MODE_NONE => 'No authentication',
        self::MAILER_AUTH_MODE_PLAIN => 'Yes (PLAIN)',
        self::MAILER_AUTH_MODE_LOGIN => 'Yes (LOGIN)',
        self::MAILER_AUTH_MODE_CRAM_MD5 => 'Yes (CRAM-MD5)',
    ];

    public const PRICING_MODE_WITHOUT_TAXES = 1;
    public const PRICING_MODE_WITH_TAXES = 2;
    public const PRICING_MODES = [
        self::PRICING_MODE_WITHOUT_TAXES => 'Tax exclusive pricing',
        self::PRICING_MODE_WITH_TAXES => 'Tax inclusive pricing',
    ];
    public const POSSIBLE_PRICING_MODES = [
        self::PRICING_MODE_WITHOUT_TAXES,
        self::PRICING_MODE_WITH_TAXES,
    ];

    public const QOS_DESTINATION_GATEWAY = 'gateway';
    public const QOS_DESTINATION_CUSTOM = 'custom';
    public const QOS_DESTINATIONS = [
        self::QOS_DESTINATION_GATEWAY => 'Gateway routers',
        self::QOS_DESTINATION_CUSTOM => 'Custom defined routers or CPE device',
    ];

    public const QOS_SYNC_TYPE_ADDRESS_LISTS = 'address_lists';
    public const QOS_SYNC_TYPES = [
        self::QOS_SYNC_TYPE_ADDRESS_LISTS => 'Address lists',
    ];

    public const QOS_INTERFACE_AIR_OS_WLAN = 1;
    public const QOS_INTERFACE_AIR_OS_EGRESS = 2;
    public const QOS_INTERFACE_AIR_OS_TYPES = [
        self::QOS_INTERFACE_AIR_OS_WLAN => 'WLAN - Egress, WLAN - Ingress',
        self::QOS_INTERFACE_AIR_OS_EGRESS => 'WLAN - Egress, LAN - Egress',
    ];

    public const SYNC_FREQUENCY_1 = 1;
    public const SYNC_FREQUENCY_6 = 6;
    public const SYNC_FREQUENCY_12 = 12;
    public const SYNC_FREQUENCY_24 = 24;
    public const SYNC_FREQUENCIES = [
        self::SYNC_FREQUENCY_1 => '1 hour',
        self::SYNC_FREQUENCY_6 => '6 hours',
        self::SYNC_FREQUENCY_12 => '12 hours',
        self::SYNC_FREQUENCY_24 => '24 hours',
    ];

    public const CLIENT_ID_TYPE_DEFAULT = 1;
    public const CLIENT_ID_TYPE_CUSTOM = 2;
    public const CLIENT_ID_TYPES = [
        self::CLIENT_ID_TYPE_DEFAULT => 'Default',
        self::CLIENT_ID_TYPE_CUSTOM => 'Custom',
    ];

    public const BALANCE_STYLE_TYPE_EU = 'EU';
    public const BALANCE_STYLE_TYPE_US = 'US';
    public const BALANCE_STYLES = [
        self::BALANCE_STYLE_TYPE_EU => 'show client credit as positive balance / show client debit as negative balance',
        self::BALANCE_STYLE_TYPE_US => 'show client credit as negative balance / show client debit as positive balance',
    ];

    public const INVOICE_PERIOD_START_DAY_TODAY = 0;

    public const UPDATE_CHANNEL_STABLE = 'stable';
    public const UPDATE_CHANNEL_BETA = 'beta';
    public const UPDATE_CHANNELS = [
        self::UPDATE_CHANNEL_STABLE,
        self::UPDATE_CHANNEL_BETA,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="option_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=128, unique=true)
     */
    protected $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     * @Assert\Length(max = 500)
     */
    protected $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Option
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): Option
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): string
    {
        if (! array_key_exists($this->code, self::TYPE_CASTS)) {
            @trigger_error(sprintf('Type for code %s does not exist.', $this->code), E_USER_DEPRECATED);

            return 'string';
        }

        return self::TYPE_CASTS[$this->code];
    }

    /**
     * @return bool|int|float|string
     */
    public function getTypedValue()
    {
        $type = $this->getType();

        if ($this->value !== null) {
            switch ($type) {
                case 'bool':
                    return (bool) $this->value;
                case 'int':
                    return (int) $this->value;
                case 'float':
                    return (float) $this->value;
            }
        }

        return $this->value;
    }

    public function getName(): ?string
    {
        if (! array_key_exists($this->code, self::NAMES)) {
            @trigger_error(sprintf('Name for code %s does not exist.', $this->code), E_USER_DEPRECATED);

            return $this->code;
        }

        return self::NAMES[$this->code];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Option %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage(): array
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage(): array
    {
        $message['logMsg'] = [
            'message' => 'Option %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogIgnoredColumns(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return null;
    }
}
