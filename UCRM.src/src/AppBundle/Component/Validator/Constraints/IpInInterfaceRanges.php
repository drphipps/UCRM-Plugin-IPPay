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
class IpInInterfaceRanges extends Constraint
{
    /**
     * @var string
     */
    public $messageIpNotInRanges = 'IP address {{ value }} is out of supported ranges on device interface {{ interface }}.';

    /**
     * @var string
     */
    public $messageIpUsedOnInterface = 'IP address {{ value }} is used on device interface {{ interface }}.';
}
