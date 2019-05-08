<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueGoogleCalendarIdValidator extends ConstraintValidator
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function validate($value, Constraint $constraint)
    {
        if (! $constraint instanceof UniqueGoogleCalendarId) {
            throw new UnexpectedTypeException($constraint, UniqueGoogleCalendarId::class);
        }

        if (! $value) {
            return;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(
            [
                'googleCalendarId' => $value,
            ]
        );

        /** @var User $userEntity */
        $userEntity = $this->context->getObject();

        if ($user && $userEntity && $user->getId() !== $userEntity->getId()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
