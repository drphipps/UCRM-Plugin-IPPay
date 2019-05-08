<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Interfaces;

interface TicketActivityWithEmailInterface
{
    public function getCreatedAt(): \DateTime;

    public function getEmailId(): ?string;

    public function setEmailId(?string $emailId): void;
}
