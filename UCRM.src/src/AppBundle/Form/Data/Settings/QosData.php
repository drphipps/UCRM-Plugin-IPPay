<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;

final class QosData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::QOS_ENABLED)
     */
    public $qosEnabled;

    /**
     * @var string
     *
     * @Identifier(Option::QOS_SYNC_TYPE)
     */
    public $qosSyncType;

    /**
     * @var string
     *
     * @Identifier(Option::QOS_DESTINATION)
     */
    public $qosDestination;

    /**
     * @var int
     *
     * @Identifier(Option::QOS_INTERFACE_AIR_OS)
     */
    public $qosInterfaceAirOs;
}
