<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use Nette\Utils\Strings;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FqdnValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof Fqdn) {
            throw new UnexpectedTypeException($constraint, Fqdn::class);
        }

        if ($value && ! Strings::match($value, Fqdn::PATTERN)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
