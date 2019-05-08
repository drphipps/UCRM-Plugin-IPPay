<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation()
 */
class PasswordStrength extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Please choose a more secure password.';
}
