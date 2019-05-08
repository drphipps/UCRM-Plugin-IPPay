<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Sync\Exceptions;

class QoSSyncNotSupportedException extends \RuntimeException
{
    /**
     * @var string
     */
    private $system;

    /**
     * @var string
     */
    private $expected;

    public function __construct(string $system, $value, string $expected)
    {
        $this->system = $system;
        $this->expected = $expected;

        parent::__construct(
            sprintf(
                'QoS Sync is only supported for "%s" on "%s" system, "%s" given.',
                $expected,
                $system,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    public function getSystem(): string
    {
        return $this->system;
    }

    public function getExpected(): string
    {
        return $this->expected;
    }
}
