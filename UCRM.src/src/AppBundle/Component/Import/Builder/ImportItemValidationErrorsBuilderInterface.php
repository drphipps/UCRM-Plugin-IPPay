<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Builder;

use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ImportItemValidationErrorsBuilderInterface
{
    public function addViolationList(ConstraintViolationListInterface $violationList): void;

    public function addTransformerViolation(
        string $message,
        string $propertyPath,
        ?string $invalidValue = null,
        array $parameters = []
    ): void;
}
