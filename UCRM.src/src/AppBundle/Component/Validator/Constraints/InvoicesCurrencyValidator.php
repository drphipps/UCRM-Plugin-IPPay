<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Payment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InvoicesCurrencyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof InvoicesCurrency) {
            throw new UnexpectedTypeException($constraint, InvoicesCurrency::class);
        }

        if (! is_iterable($value)) {
            return;
        }

        $payment = $this->context->getRoot()->getData();
        if (! $payment instanceof Payment) {
            return;
        }

        /** @var Invoice $invoice */
        foreach ($value as $invoice) {
            if ($invoice->getCurrency() !== $payment->getCurrency()) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
            break;
        }
    }
}
