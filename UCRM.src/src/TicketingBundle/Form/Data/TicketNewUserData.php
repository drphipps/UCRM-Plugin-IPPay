<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form\Data;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class TicketNewUserData
{
    /**
     * @var array
     */
    public $attachmentFiles = [];

    /**
     * @var string
     *
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    public $subject;

    /**
     * @var string|null
     */
    public $message;

    /**
     * @var Client
     *
     * @Assert\NotNull()
     */
    public $client;

    /**
     * @var User|null
     */
    public $user;

    /**
     * @var bool
     */
    public $private = false;
}
