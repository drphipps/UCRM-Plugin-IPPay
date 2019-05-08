<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use AppBundle\Entity\ClientContact;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueLoginValidator extends ConstraintValidator
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
        if (! $constraint instanceof UniqueLogin) {
            throw new UnexpectedTypeException($constraint, UniqueLogin::class);
        }

        $user = $this->em->getRepository(User::class)->loadUserByUsername($value);

        /** @var User $userEntity */
        $userEntity = $this->context->getObject();

        if ($user && $userEntity && $user->getId() !== $userEntity->getId()) {
            $contacts = $userEntity->getClient() ? $userEntity->getClient()->getContacts() : new ArrayCollection();
            $loginContact = $contacts->filter(
                function (ClientContact $contact) {
                    return $contact->getIsLogin() && $contact->getEmail();
                }
            )->first();

            $message = $loginContact ? $constraint->emailDuplicateMessage : $constraint->message;
            $this->context->buildViolation($message)->addViolation();
        }
    }
}
