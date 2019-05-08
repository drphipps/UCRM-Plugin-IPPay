<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class QosCycle extends Constraint
{
    /**
     * @var string
     */
    public $message = 'QoS cycle detected on "%device%".';
}
