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

final class LogsData implements SettingsDataInterface
{
    /**
     * @var int
     *
     * @Identifier(Option::LOG_LIFETIME_DEVICE)
     *
     * @Assert\Range(min = 1)
     * @Assert\NotBlank()
     */
    public $logLifetimeDevice;

    /**
     * @var int
     *
     * @Identifier(Option::LOG_LIFETIME_EMAIL)
     *
     * @Assert\Range(min = 1)
     * @Assert\NotBlank()
     */
    public $logLifetimeEmail;

    /**
     * @var int
     *
     * @Identifier(Option::LOG_LIFETIME_ENTITY)
     *
     * @Assert\Range(min = 1)
     * @Assert\NotBlank()
     */
    public $logLifetimeEntity;

    /**
     * @var int
     *
     * @Identifier(Option::LOG_LIFETIME_SERVICE_DEVICE)
     *
     * @Assert\Range(min = 1)
     * @Assert\NotBlank()
     */
    public $logLifetimeServiceDevice;

    /**
     * @var int
     *
     * @Identifier(Option::HEADER_NOTIFICATIONS_LIFETIME)
     *
     * @Assert\Range(min = 1)
     * @Assert\NotBlank()
     */
    public $headerNotificationsLifetime;
}
