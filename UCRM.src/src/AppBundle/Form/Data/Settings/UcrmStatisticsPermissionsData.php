<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

class UcrmStatisticsPermissionsData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::SEND_ANONYMOUS_STATISTICS))
     */
    public $sendAnonymousStatistics;
}
