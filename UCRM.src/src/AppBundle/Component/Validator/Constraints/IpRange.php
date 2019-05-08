<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class IpRange extends Constraint
{
    /**
     * @var string
     */
    public $message = 'IP address {{ value }} is invalid.';
}
