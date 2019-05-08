<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class SyncData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::SYNC_ENABLED)
     */
    public $syncEnabled;

    /**
     * @var int
     *
     * @Identifier(Option::SYNC_FREQUENCY)
     */
    public $syncFrequency;
}
