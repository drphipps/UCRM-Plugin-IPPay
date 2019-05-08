<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Component\Serializer;

use ApiBundle\Component\Validator\ValidationHttpException;
use ApiBundle\Map\AbstractMap;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class JsonStrictDeserializationVisitor extends JsonDeserializationVisitor
{
    /**
     * Overridden to stop type casting.
     *
     * {@inheritdoc}
     */
    public function visitString($data, array $type, Context $context)
    {
        return $data;
    }

    /**
     * Overridden to stop type casting.
     *
     * {@inheritdoc}
     */
    public function visitBoolean($data, array $type, Context $context)
    {
        return $data;
    }

    /**
     * Overridden to stop type casting.
     *
     * {@inheritdoc}
     */
    public function visitInteger($data, array $type, Context $context)
    {
        return $data;
    }

    /**
     * Overridden to stop type casting.
     *
     * {@inheritdoc}
     */
    public function visitDouble($data, array $type, Context $context)
    {
        return $data;
    }

    /**
     * Natively JMS serializer ignores all extra fields. This method makes its behavior more strict
     * and throws an exception in case any extra field or type mismatch occurs.
     *
     * @throws ValidationHttpException
     *
     * {@inheritdoc}
     */
    public function endVisitingObject(ClassMetadata $metadata, $data, array $type, Context $context)
    {
        $object = parent::endVisitingObject($metadata, $data, $type, $context);
        if (! $object instanceof AbstractMap) {
            throw new \RuntimeException(
                sprintf('Deserialization is only possible into %s object.', AbstractMap::class)
            );
        }

        $errors = [];

        foreach ($data as $fieldName => $fieldValue) {
            if (property_exists($object, $fieldName) && ! $metadata->propertyMetadata[$fieldName]->readOnly) {
                $this->assertType($metadata->propertyMetadata[$fieldName], $fieldValue, $errors);
                $object->addInputField($fieldName);
            } else {
                $errors[$fieldName][] = 'This field is not allowed.';
            }
        }

        if (! empty($errors)) {
            throw new ValidationHttpException($errors);
        }

        return $object;
    }

    private function assertType(PropertyMetadata $propertyMetadata, $fieldValue, array &$errors)
    {
        if (null === $fieldValue) {
            return;
        }

        $fieldType = $propertyMetadata->type['name'];
        $fieldName = $propertyMetadata->reflection->getName();
        $errorMessage = 'This value is not in valid type. %s expected.';

        switch ($fieldType) {
            case 'boolean':
                if (! is_bool($fieldValue)) {
                    $errors[$fieldName][] = sprintf($errorMessage, 'Boolean');
                }
                break;
            case 'string':
                if (! is_string($fieldValue)) {
                    $errors[$fieldName][] = sprintf($errorMessage, 'String');
                }
                break;
            case 'integer':
                if (! is_int($fieldValue)) {
                    $errors[$fieldName][] = sprintf($errorMessage, 'Integer');
                }
                break;
            case 'double':
                if (! is_float($fieldValue) && ! is_int($fieldValue)) {
                    $errors[$fieldName][] = sprintf($errorMessage, 'Double');
                }
                break;
        }
    }
}
