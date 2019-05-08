<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use AppBundle\Component\Import\Transformer\ConstraintViolationTransformer;
use AppBundle\Component\Import\Validator\TransformerConstraintViolation;
use AppBundle\Entity\Import\ImportItemValidationErrorsInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @property PropertyAccessorInterface           $propertyAccessor
 * @property ConstraintViolationTransformer      $constraintViolationTransformer
 * @property ImportItemValidationErrorsInterface $validationErrors
 */
trait ImportItemValidationErrorsBuilderTrait
{
    public function addViolationList(ConstraintViolationListInterface $violationList): void
    {
        foreach ($violationList as $violation) {
            $this->addViolation($violation);
        }
    }

    public function addTransformerViolation(
        string $message,
        string $propertyPath,
        ?string $invalidValue = null,
        array $parameters = []
    ): void {
        $this->addViolation(
            new TransformerConstraintViolation(
                $message,
                $propertyPath,
                $invalidValue,
                $parameters
            )
        );
    }

    private function addViolation(ConstraintViolationInterface $violation): void
    {
        if ($this->fieldExists($violation)) {
            $this->addFieldViolation($violation);
        } else {
            $this->addUnmappedViolation($violation);
        }
    }

    private function addFieldViolation(ConstraintViolationInterface $violation): void
    {
        $propertyPath = $this->getPropertyPath($violation);

        $errors = $this->propertyAccessor->getValue($this->validationErrors, $propertyPath);
        if (! is_array($errors)) {
            throw new \InvalidArgumentException('Error field must be array.');
        }

        foreach ($errors as $error) {
            if ($error['isTransformerViolation']) {
                // If there are errors directly from transformation, we don't want to include errors,
                // from entity validators.
                // It would be confusing, since there is for example lots of NotNull validations,
                // and the transformer does not set invalid values.
                // So the user would get both NotNull error and the transformer error.

                return;
            }
        }

        $errors[] = $this->constraintViolationTransformer->toArray($violation);
        $this->propertyAccessor->setValue($this->validationErrors, $propertyPath, $errors);
    }

    private function addUnmappedViolation(ConstraintViolationInterface $violation): void
    {
        $errors = $this->validationErrors->getUnmappedErrors();
        $errors[] = $this->constraintViolationTransformer->toArray($violation);
        $this->validationErrors->setUnmappedErrors($errors);
    }

    private function getPropertyPath(ConstraintViolationInterface $violation): string
    {
        $difference = $this->getValidationFieldsDifference(
            is_object($violation->getRoot()) ? get_class($violation->getRoot()) : null
        );

        return $difference[$violation->getPropertyPath()] ?? $violation->getPropertyPath();
    }

    private function fieldExists(ConstraintViolationInterface $violation): bool
    {
        try {
            return $this->propertyAccessor->isWritable($this->validationErrors, $this->getPropertyPath($violation));
        } catch (\Exception $exception) {
            return false;
        }
    }
}
