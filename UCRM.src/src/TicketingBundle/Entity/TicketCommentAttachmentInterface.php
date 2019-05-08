<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Entity;

interface TicketCommentAttachmentInterface
{
    public function getFilename(): string;

    public function getMimeType(): ?string;

    public function getSize(): int;

    public function getTicketComment(): TicketComment;

    public function getPartId(): ?string;
}
