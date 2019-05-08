<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class FirstLoginData
{
    /**
     * @var string
     *
     * @Assert\Length(
     *     min = 8,
     *     max = 72,
     *     minMessage = "Password must be at least {{ limit }} characters long."
     * )
     */
    public $password;
}
