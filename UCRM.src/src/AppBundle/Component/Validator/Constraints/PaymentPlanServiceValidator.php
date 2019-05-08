<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PaymentPlanServiceValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof PaymentPlanService) {
            throw new UnexpectedTypeException($constraint, PaymentPlanService::class);
        }

        if (null === $value) {
            return;
        }

        if (! $value instanceof Service) {
            throw new UnexpectedTypeException($value, Service::class);
        }

        $existingPaymentPlans = $this->entityManager->getRepository(PaymentPlan::class)->findBy(
            [
                'service' => $value,
                'active' => true,
            ]
        );

        if (count($existingPaymentPlans) > 0) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
