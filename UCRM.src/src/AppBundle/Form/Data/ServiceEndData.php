<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class ServiceEndData
{
    /**
     * @Assert\Type("boolean")
     *
     * @var bool
     */
    public $allowEarlyTerminationFee = true;
}
