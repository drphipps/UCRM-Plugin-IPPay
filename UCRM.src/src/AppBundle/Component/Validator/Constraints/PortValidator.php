<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PortValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof Port) {
            throw new UnexpectedTypeException($constraint, Port::class);
        }

        if (null !== $value && ($value < 1 || $value > 65535)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
