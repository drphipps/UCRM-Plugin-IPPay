<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class StripeAchVerifyData
{
    /**
     * @var int
     *
     * @Assert\Range(min=1, max=100)
     * @Assert\NotNull()
     */
    public $firstDeposit;

    /**
     * @var int
     *
     * @Assert\Range(min=1, max=100)
     * @Assert\NotNull()
     */
    public $secondDeposit;
}
