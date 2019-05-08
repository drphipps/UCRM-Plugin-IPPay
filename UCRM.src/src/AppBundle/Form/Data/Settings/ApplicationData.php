<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class ApplicationData extends UcrmStatisticsPermissionsData implements SettingsDataInterface
{
    /**
     * @var string
     *
     * @Identifier(Option::SERVER_FQDN)
     *
     * @CustomAssert\Fqdn()
     *
     * @Assert\Length(max=500)
     */
    public $serverFqdn;

    /**
     * @var string
     *
     * @Identifier(Option::SERVER_IP)
     *
     * @Assert\Ip()
     */
    public $serverIp;

    /**
     * @var int
     *
     * @Identifier(Option::SERVER_PORT)
     *
     * @CustomAssert\Port()
     * @Assert\NotNull()
     */
    public $serverPort;

    /**
     * @var int
     *
     * @Identifier(Option::SERVER_SUSPEND_PORT)
     *
     * @CustomAssert\Port()
     * @Assert\NotNull()
     */
    public $serverSuspendPort;

    /**
     * @var string
     *
     * @Identifier(Option::SITE_NAME)
     *
     * @Assert\Length(max=500)
     */
    public $siteName;

    /**
     * @var string
     *
     * @Identifier(Option::MAPBOX_TOKEN)
     *
     * @Assert\Length(max=500)
     */
    public $mapboxToken;

    /**
     * @var string
     *
     * @Identifier(Option::GOOGLE_API_KEY)
     *
     * @Assert\Length(max=500)
     */
    public $googleApiKey;

    /**
     * @var string
     *
     * @Identifier(Option::PDF_PAGE_SIZE_EXPORT)
     */
    public $exportPageSize;

    /**
     * @var string
     *
     * @Identifier(Option::PDF_PAGE_SIZE_INVOICE)
     */
    public $invoicePageSize;

    /**
     * @var string
     *
     * @Identifier(Option::PDF_PAGE_SIZE_PAYMENT_RECEIPT)
     */
    public $paymentReceiptPageSize;

    /**
     * @var bool
     *
     * @Identifier(Option::ERROR_REPORTING)
     */
    public $errorReporting;

    /**
     * @var int
     *
     * @Identifier(Option::CLIENT_ID_TYPE)
     */
    public $clientIdType;

    /**
     * @var int
     *
     * @Assert\LessThanOrEqual(2147483647)
     * @CustomAssert\ClientIdMax()
     */
    public $clientIdNext;

    /**
     * @var int
     *
     * @Identifier(Option::BALANCE_STYLE)
     */
    public $balanceStyle;
}
