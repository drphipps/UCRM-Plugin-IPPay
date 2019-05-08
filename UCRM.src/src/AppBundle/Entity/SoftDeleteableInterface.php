<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

interface SoftDeleteableInterface
{
    public function setDeletedAt(?\DateTime $deletedAt): void;

    public function getDeletedAt(): ?\DateTime;

    public function isDeleted(): bool;
}
