<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator;

use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator
{
    /**
     * @var PropertyAccessorInterface
     */
    private $accessor;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var array
     */
    private $isWritableCache = [];

    public function __construct(
        ValidatorInterface $validator,
        ?PropertyAccessorInterface $accessor = null
    ) {
        $this->validator = $validator;
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function validateEntityByProperties(
        ValidatorInterface $validator,
        string $entityClass,
        array $properties,
        array $validationGroups = ['Default']
    ): array {
        $entity = new $entityClass();

        foreach ($properties as $property => $value) {
            if (! $this->isWritable($entityClass, $entity, $property)) {
                continue;
            }

            try {
                // intentional @, we need to handle cases where e.g. string is set to an int field
                // this is problem in CSV import, the validator will handle it correctly then
                @$this->accessor->setValue($entity, $property, $value);
            } catch (InvalidArgumentException $exception) {
                // ignore silently, validator will handle it if needed
            }
        }

        $errors = [];
        $violations = $validator->validate($entity, null, $validationGroups);

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }

    private function isWritable(string $entityClass, $entity, string $property): bool
    {
        $key = $entityClass . '..' . $property;
        if (! array_key_exists($key, $this->isWritableCache)) {
            $this->isWritableCache[$key] = $this->accessor->isWritable($entity, $property);
        }

        return $this->isWritableCache[$key];
    }
}
