<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Component\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ValidationErrorCollector
     */
    private $errorCollector;

    public function __construct(ValidatorInterface $validator, ValidationErrorCollector $errorCollector)
    {
        $this->validator = $validator;
        $this->errorCollector = $errorCollector;
    }

    /**
     * @param Constraint|Constraint[] $constraints
     */
    public function validate(
        $value,
        array $fieldsDifference = [],
        $constraints = null,
        array $groups = null
    ): Validator {
        $this->doValidate($value, $fieldsDifference, $constraints, $groups);

        $this->errorCollector->throwErrors();

        return $this;
    }

    public function validatePostpone(
        $value,
        array $fieldsDifference = [],
        $constraints = null,
        array $groups = null
    ): Validator {
        $this->doValidate($value, $fieldsDifference, $constraints, $groups);

        return $this;
    }

    public function throwErrors(): Validator
    {
        $this->errorCollector->throwErrors();

        return $this;
    }

    /**
     * @param Constraint[]|Constraint|null $constraints
     */
    public function doValidate(
        $value,
        array $fieldsDifference = [],
        $constraints = null,
        array $groups = null
    ) {
        $errors = $this->validator->validate($value, $constraints, $groups);

        foreach ($errors as $error) {
            $propertyPath = $fieldsDifference[$error->getPropertyPath()] ?? $error->getPropertyPath();
            $this->errorCollector->add($propertyPath, $error->getMessage());
        }
    }
}
