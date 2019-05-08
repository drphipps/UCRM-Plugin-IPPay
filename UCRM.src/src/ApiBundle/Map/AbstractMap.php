<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy(ExclusionPolicy::NONE)
 */
abstract class AbstractMap
{
    /**
     * @var array
     *
     * @Exclude()
     */
    private $inputFields = [];

    /**
     * @throws \InvalidArgumentException
     */
    public function addInputField(string $fieldName)
    {
        if (! property_exists($this, $fieldName)) {
            throw new \InvalidArgumentException(
                sprintf('Field %s does not exist for class %s', $fieldName, static::class)
            );
        }

        $this->inputFields[$fieldName] = true;
    }

    public function isFieldInInput(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->inputFields);
    }
}
