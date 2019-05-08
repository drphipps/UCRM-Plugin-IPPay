<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

interface TicketActivityAssignmentInterface
{
    public const TYPE_ADD = 'add';
    public const TYPE_REMOVE = 'remove';

    public const POSSIBLE_TYPES = [
        self::TYPE_ADD,
        self::TYPE_REMOVE,
    ];

    public function getType(): string;

    public function setType(string $type): void;
}
