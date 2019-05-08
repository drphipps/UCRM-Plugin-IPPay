<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\Tax;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FinancialItemPricingModeTaxValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof FinancialItemPricingModeTax) {
            throw new UnexpectedTypeException($constraint, FinancialItemPricingModeTax::class);
        }

        if (null === $value) {
            return;
        }

        if (! $value instanceof Tax) {
            throw new UnexpectedTypeException($value, Tax::class);
        }

        $item = $this->context->getObject();
        if (! $item instanceof FinancialItemInterface) {
            throw new UnexpectedTypeException($item, FinancialItemInterface::class);
        }

        if ($item->getFinancial()->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES) {
            return;
        }

        if ($item->getTax2() || $item->getTax3()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
