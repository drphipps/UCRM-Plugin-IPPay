<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Plugin;
use Symfony\Component\Validator\Constraints as Assert;

class PluginConfigurationData
{
    /**
     * @var string|null
     *
     * @Assert\Choice(choices=Plugin::EXECUTION_PERIODS, strict=true)
     */
    public $executionPeriod;

    /**
     * @var array
     */
    public $configuration = [];
}
