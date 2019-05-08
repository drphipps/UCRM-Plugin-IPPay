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

final class WizardMailerData extends MailerData
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
}
