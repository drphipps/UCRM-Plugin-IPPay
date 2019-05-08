<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Entity\Ticket;

class TicketCommentClientData
{
    /**
     * @var array
     */
    public $attachmentFiles;

    /**
     * @var string
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
}
