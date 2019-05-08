<?php
/*
 * @copyright Copyright (c) 2017 Ubiquiti Networks, Inc
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueLogin extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This username is already taken.';

    /**
     * @var string
     */
    public $emailDuplicateMessage = 'This email is already used as the username of another client, please use a different email address for this client, or set a custom username.';
}
