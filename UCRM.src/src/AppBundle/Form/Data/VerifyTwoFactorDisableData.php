<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;

class VerifyTwoFactorDisableData
{
    /**
     * @var string
     *
     * @SecurityAssert\UserPassword(
     *     message = "Wrong value for your current password"
     * )
     */
    public $password;
}
