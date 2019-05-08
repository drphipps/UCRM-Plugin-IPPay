<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Import\Validator;

use Symfony\Component\Validator\ConstraintViolation;

class TransformerConstraintViolation extends ConstraintViolation
{
    public function __construct(
        string $message,
        string $propertyPath,
        ?string $invalidValue = null,
        array $parameters = []
    ) {
        parent::__construct(
            $message,
            $message,
            $parameters,
            null,
            $propertyPath,
            $invalidValue
        );
    }
}
