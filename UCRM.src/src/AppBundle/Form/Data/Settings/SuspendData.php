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

final class SuspendData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::SUSPEND_ENABLED)
     */
    public $suspendEnabled;

    /**
     * @var bool
     *
     * @Identifier(Option::STOP_SERVICE_DUE)
     */
    public $stopServiceDue;

    /**
     * @var int
     *
     * @Identifier(Option::STOP_SERVICE_DUE_DAYS)
     *
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\NotBlank()
     */
    public $stopServiceDueDays;

    /**
     * @var float
     *
     * @Identifier(Option::SUSPENSION_MINIMUM_UNPAID_AMOUNT)
     *
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\NotBlank()
     */
    public $suspensionMinimumUnpaidAmount;

    /**
     * @var bool
     *
     * @Identifier(Option::SUSPENSION_ENABLE_POSTPONE)
     */
    public $suspensionEnablePostpone;
}
