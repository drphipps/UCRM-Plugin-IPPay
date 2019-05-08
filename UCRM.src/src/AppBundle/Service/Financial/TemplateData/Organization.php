<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class Organization
{
    /**
     * @var int|null
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $registrationNumber;

    /**
     * @var string
     */
    public $taxId;

    /**
     * @var string
     */
    public $logo;

    /**
     * @var string
     */
    public $logoOriginal;

    /**
     * @var string
     */
    public $stamp;

    /**
     * @var string
     */
    public $stampOriginal;

    /**
     * @var string
     */
    public $street1;

    /**
     * @var string
     */
    public $street2;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $zipCode;

    /**
     * @var string
     */
    public $bankAccount;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $website;

    /**
     * @var bool
     */
    public $hasPaymentGateway;
}
