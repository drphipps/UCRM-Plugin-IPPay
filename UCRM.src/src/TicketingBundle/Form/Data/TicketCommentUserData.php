<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\Ticket;

class TicketCommentUserData
{
    /**
     * @var array
     */
    public $attachmentFiles;

    /**
     * @var string|null
     *
     * @Assert\Expression(
     *     expression="value or this.attachmentFiles",
     *     message="You must add a text or upload a file."
     * )
     */
    public $body;

    /**
     * @var Ticket
     *
     * @Assert\NotBlank()
     */
    public $ticket;

    /**
     * @var bool
     *
     * @Assert\NotNull()
     */
    public $private = false;
}
