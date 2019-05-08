<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Component\Validator\Constraints as CustomAssert;
use AppBundle\Entity\Locale;
use AppBundle\Entity\Option;
use AppBundle\Entity\Timezone;
use AppBundle\Form\Data\Settings\SettingsDataInterface;
use AppBundle\Security\PasswordStrengthInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WizardData implements SettingsDataInterface, PasswordStrengthInterface
{
    public const DEFAULT_EMAIL = 'admin@example.com';

    /**
     * @var string
     *
     * @Assert\Length(
     *     max = 72,
     *     min = 8,
     *     minMessage = "Password must be at least {{ limit }} characters long."
     * )
     * @CustomAssert\PasswordStrength()
     */
    public $password;

    /**
     * @var string
     *
     * @Assert\Length(max = 320)
     * @Assert\NotBlank()
     */
    public $username;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 320)
     * @Assert\Email(
     *     strict=true
     * )
     */
    public $email;

    /**
     * @var string
     *
     * @Assert\Length(max = 255)
     */
    public $firstName;

    /**
     * @var string
     *
     * @Assert\Length(max = 255)
     */
    public $lastName;

    /**
     * @var Locale|null
     *
     * @Assert\NotBlank()
     */
    public $locale;

    /**
     * @var string
     *
     * @Identifier(Option::APP_LOCALE)
     */
    public $localeOption;

    /**
     * @var Timezone|null
     *
     * @Assert\NotBlank()
     */
    public $timezone;

    /**
     * @var string
     *
     * @Identifier(Option::APP_TIMEZONE)
     */
    public $timezoneOption;

    public function getPasswordStrengthExtraData(): array
    {
        return array_filter(
            [
                $this->username,
                $this->firstName,
                $this->lastName,
                $this->email,
            ]
        );
    }

    public function shouldCheckPasswordStrength(): bool
    {
        return true;
    }
}
