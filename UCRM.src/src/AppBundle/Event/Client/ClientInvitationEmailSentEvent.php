<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Event\Client;

use Symfony\Component\EventDispatcher\Event;

final class ClientInvitationEmailSentEvent extends Event
{
    /**
     * @var int
     */
    private $clientId;

    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }
}
