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

final class MailerLimiterData implements SettingsDataInterface
{
    public const MAILER_THROTTLER_LIMIT_TIME_UNIT_MINUTES = 'minutes';
    public const MAILER_THROTTLER_LIMIT_TIME_UNIT_HOURS = 'hours';
    public const MAILER_THROTTLER_LIMIT_TIME_UNITS = [
        'minutes' => self::MAILER_THROTTLER_LIMIT_TIME_UNIT_MINUTES,
        'hours' => self::MAILER_THROTTLER_LIMIT_TIME_UNIT_HOURS,
    ];

    /**
     * @var bool
     */
    public $useAntiflood = false;

    /**
     * @var int|null
     *
     * @Identifier(Option::MAILER_ANTIFLOOD_LIMIT_COUNT)
     *
     * @Assert\GreaterThanOrEqual(value = 1)
     */
    public $mailerAntifloodLimitCount;

    /**
     * @var int|null
     *
     * @Identifier(Option::MAILER_ANTIFLOOD_SLEEP_TIME)
     *
     * @Assert\GreaterThanOrEqual(value = 1)
     * @Assert\LessThanOrEqual(value = 86400)
     */
    public $mailerAntifloodSleepTime;

    /**
     * @var bool
     */
    public $useThrottler = false;

    /**
     * @var int|null
     *
     * @Identifier(Option::MAILER_THROTTLER_LIMIT_COUNT)
     *
     * @Assert\GreaterThanOrEqual(value = 1)
     */
    public $mailerThrottlerLimitCount;

    /**
     * @var int|null
     *
     * @Identifier(Option::MAILER_THROTTLER_LIMIT_TIME)
     *
     * @Assert\GreaterThanOrEqual(value = 1)
     */
    public $mailerThrottlerLimitTime;

    /**
     * @var string
     */
    public $mailerThrottlerLimitTimeUnit;
}
