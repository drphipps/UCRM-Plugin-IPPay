<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\User;
use AppBundle\Security\PasswordStrengthInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetData implements PasswordStrengthInterface
{
    /**
     * @var string
     *
     * @Assert\Length(
     *     min = 8,
     *     max = 72,
     *     minMessage = "Password must be at least {{ limit }} characters long."
     * )
     * @CustomAssert\PasswordStrength()
     */
    public $password;

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
        return $this->user->shouldCheckPasswordStrength();
    }
}
