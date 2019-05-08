<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Exception;

class UnexpectedTypeException extends \InvalidArgumentException
{
    public function __construct($value, $expectedType)
    {
        parent::__construct(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                $expectedType,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
