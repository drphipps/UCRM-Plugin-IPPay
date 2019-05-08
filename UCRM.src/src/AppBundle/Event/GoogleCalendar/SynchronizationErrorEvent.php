<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\GoogleCalendar;

use AppBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class SynchronizationErrorEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(User $user, \Exception $exception)
    {
        $this->user = $user;
        $this->exception = $exception;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}
