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

final class NetFlowOptionsData implements SettingsDataInterface
{
    /**
     * @var int
     *
     * @Identifier(Option::NETFLOW_AGGREGATION_FREQUENCY)
     *
     * @Assert\Range(min=1)
     * @Assert\NotBlank()
     */
    public $netflowAggregationFrequency;

    /**
     * @var int
     *
     * @Identifier(Option::NETFLOW_MINIMUM_UNKNOWN_TRAFFIC)
     *
     * @Assert\Range(min=0)
     * @Assert\NotBlank()
     */
    public $netflowMinimumUnknownTraffic;
}
