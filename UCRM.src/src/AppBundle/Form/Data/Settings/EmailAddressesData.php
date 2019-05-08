<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class EmailAddressesData implements SettingsDataInterface
{
    /**
     * @var string
     *
     * @Identifier(Option::MAILER_SENDER_ADDRESS)
     *
     * @Assert\Email(
     *     strict=true
     * )
     * @Assert\Length(max=500)
     */
    public $mailerSenderAddress;

    /**
     * @var string
     *
     * @Identifier(Option::SUPPORT_EMAIL_ADDRESS)
     *
     * @Assert\Email(
     *     strict=true
     * )
     * @Assert\Length(max=500)
     */
    public $supportEmailAddress;

    /**
     * @var string
     *
     * @Identifier(Option::NOTIFICATION_EMAIL_ADDRESS)
     *
     * @Assert\Email(
     *     strict=true
     * )
     * @Assert\Length(max=500)
     */
    public $notificationEmailAddress;
}
