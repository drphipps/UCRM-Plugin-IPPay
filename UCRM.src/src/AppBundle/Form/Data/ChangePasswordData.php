<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form\Data;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\User;
use AppBundle\Security\PasswordStrengthInterface;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordData implements PasswordStrengthInterface
{
    /**
     * @SecurityAssert\UserPassword(
     *     message = "Wrong value for your current password"
     * )
     */
    public $oldPassword;

    /**
     * @Assert\Length(
     *     min = 8,
     *     max = 72,
     *     minMessage = "Password must be at least {{ limit }} characters long."
     * )
     * @CustomAssert\PasswordStrength()
     */
    public $newPassword;

    /**
     * @var User
     */
    public $user;

    public function getPasswordStrengthExtraData(): array
    {
        return $this->user->getPasswordStrengthExtraData();
    }

    public function shouldCheckPasswordStrength(): bool
    {
        return $this->user->isAdmin();
    }
}
