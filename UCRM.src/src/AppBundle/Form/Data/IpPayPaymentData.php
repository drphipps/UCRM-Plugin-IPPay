<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Country;
use Symfony\Component\Validator\Constraints as Assert;

class IpPayPaymentData
{
    /**
     * @var string
     *
     * @Assert\NotNull()
     */
    public $cardNumber;

    /**
     * @var string
     *
     * @Assert\Regex(pattern="~^(?:0[0-9]|1[012]) ?/ ?[0-9]{2}$~")
     * @Assert\NotNull()
     */
    public $cardExpiration;

    /**
     * @var string
     *
     * @Assert\NotNull()
     */
    public $CVV2;

    /**
     * @var string
     */
    public $address;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state;

    /**
     * @var Country
     */
    public $country;

    /**
     * @var string
     * @Assert\Length(max="10")
     */
    public $zipCode;

    public function __debugInfo()
    {
        return [];
    }
}
