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
class UniqueGoogleCalendarId extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This Google Calendar is already used by another user.';
}
