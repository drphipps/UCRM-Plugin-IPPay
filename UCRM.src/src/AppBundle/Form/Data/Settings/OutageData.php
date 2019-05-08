<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class OutageData implements SettingsDataInterface
{
    /**
     * @var int
     *
     * @Identifier(Option::PING_OUTAGE_THRESHOLD)
     *
     * @Assert\NotBlank()
     * @Assert\Range(min=0, max=100)
     */
    public $pingOutageThreshold;

    /**
     * @var int|User
     *
     * @Identifier(Option::NOTIFICATION_PING_USER)
     */
    public $notificationPingUser;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_PING_DOWN)
     */
    public $notificationPingDown;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_PING_UNREACHABLE)
     */
    public $notificationPingUnreachable;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_PING_REPAIRED)
     */
    public $notificationPingRepaired;
}
