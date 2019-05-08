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
class Mac extends Constraint
{
    const PATTERN_INPUT = '^([0-9a-fA-F]{2}[:\-]{0,1}){6}$';
    const PATTERN = '~' . self::PATTERN_INPUT . '~';

    /**
     * @var string
     */
    public $message = 'This MAC address is not valid.';
}
