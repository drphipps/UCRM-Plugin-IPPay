<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace Tests\Listener;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class MemoryListener implements TestListener
{
    use TestListenerDefaultImplementation;

    private const PHPUNIT_PROPERTY_PREFIX = 'PHPUnit_';

    public function endTest(Test $test, float $time): void
    {
        $testReflection = new \ReflectionObject($test);

        $this->safelyFreeProperties($test, $testReflection->getProperties());
    }

    private function safelyFreeProperties(Test $test, array $properties): void
    {
        foreach ($properties as $property) {
            if ($this->isSafeToFreeProperty($property)) {
                $this->freeProperty($test, $property);
            }
        }
    }

    private function isSafeToFreeProperty(\ReflectionProperty $property): bool
    {
        return ! $property->isStatic() && $this->isNotPhpUnitProperty($property);
    }

    private function isNotPhpUnitProperty(\ReflectionProperty $property): bool
    {
        return 0 !== strpos($property->getDeclaringClass()->getName(), self::PHPUNIT_PROPERTY_PREFIX);
    }

    private function freeProperty(Test $test, \ReflectionProperty $property): void
    {
        $property->setAccessible(true);
        $property->setValue($test, null);
    }
}
