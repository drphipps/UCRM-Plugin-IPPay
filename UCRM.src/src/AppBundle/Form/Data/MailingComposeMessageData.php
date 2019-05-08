<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class MailingComposeMessageData
{
    /**
     * @var string
     *
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    public $subject;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    public $body;
}
