<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordData
{
    /**
     * @var string|null
     *
     * @Assert\Length(max = 320)
     * @Assert\NotBlank()
     */
    public $username;
}
