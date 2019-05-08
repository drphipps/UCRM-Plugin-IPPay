<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Form\Data\Settings\SettingsDataInterface;
use AppBundle\Form\Data\Settings\UcrmStatisticsPermissionsData;

class WizardFinishingData extends UcrmStatisticsPermissionsData implements SettingsDataInterface
{
    /**
     * @var bool
     */
    public $enableDemoMode;

    /**
     * @var string|null
     */
    public $feedbackAllowed;
}
