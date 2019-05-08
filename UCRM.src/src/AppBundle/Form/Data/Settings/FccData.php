<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class FccData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::FCC_ALWAYS_USE_GPS)
     */
    public $alwaysUseGps;
}
