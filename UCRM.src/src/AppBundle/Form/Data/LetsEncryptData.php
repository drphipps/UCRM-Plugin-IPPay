<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class LetsEncryptData
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Email(
     *     strict=true
     * )
     * @Assert\Length(max=500)
     */
    public $email;
}
