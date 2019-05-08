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

class MailerData implements SettingsDataInterface
{
    /**
     * @var string
     *
     * @Identifier(Option::MAILER_TRANSPORT)
     */
    public $mailerTransport;

    /**
     * @var string
     *
     * @Identifier(Option::MAILER_USERNAME)
     * @Assert\Length(max=500)
     */
    public $mailerUsername;

    /**
     * @var string
     *
     * @Identifier(Option::MAILER_PASSWORD)
     * @Assert\Length(max=500)
     */
    public $mailerPassword;

    /**
     * @var string
     *
     * @Identifier(Option::MAILER_HOST)
     * @Assert\Length(max=500)
     */
    public $mailerHost;

    /**
     * @var int
     *
     * @Identifier(Option::MAILER_PORT)
     *
     * @CustomAssert\Port()
     */
    public $mailerPort;

    /**
     * @var string
     *
     * @Identifier(Option::MAILER_ENCRYPTION)
     */
    public $mailerEncryption;

    /**
     * @var string
     *
     * @Identifier(Option::MAILER_AUTH_MODE)
     */
    public $mailerAuthMode;

    /**
     * @var bool
     *
     * @Identifier(Option::MAILER_VERIFY_SSL_CERTIFICATES)
     */
    public $verifySslCertificate;
}
