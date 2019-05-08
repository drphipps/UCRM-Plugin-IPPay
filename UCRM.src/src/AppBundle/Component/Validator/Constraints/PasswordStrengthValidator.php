<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Security\PasswordStrengthInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use ZxcvbnPhp\Zxcvbn;

class PasswordStrengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof PasswordStrength) {
            throw new UnexpectedTypeException($constraint, PasswordStrength::class);
        }

        // we don't care about empty passwords, other validators handle that
        if (! $value) {
            return;
        }

        $object = $this->context->getObject();
        $extraData = [];
        if ($object instanceof PasswordStrengthInterface) {
            // this is mainly to handle regular client accounts (for Client Zone)
            // we don't have to be as strict for those as for administrator accounts
            if (! $object->shouldCheckPasswordStrength()) {
                return;
            }

            $extraData = $object->getPasswordStrengthExtraData();
        }

        $zxcvbn = new Zxcvbn();
        $results = $zxcvbn->passwordStrength(
            $value,
            $extraData
        );

        if (($results['score'] ?? 0) < 1) {
            $this->context->buildViolation($constraint->message)->addViolation();

            if ($results['feedback']['warning'] ?? null) {
                $this->context->buildViolation($results['feedback']['warning'])->addViolation();
            }
        }
    }
}
