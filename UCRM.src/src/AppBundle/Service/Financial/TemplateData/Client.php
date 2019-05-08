<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class Client
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $userIdent;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $companyName;

    /**
     * @var string
     */
    public $companyRegistrationNumber;

    /**
     * @var string
     */
    public $companyTaxId;

    /**
     * @var string
     */
    public $companyWebsite;

    /**
     * @var string
     */
    public $companyContactFirstName;

    /**
     * @var string
     */
    public $companyContactLastName;

    /**
     * @var bool
     */
    public $invoiceAddressSameAsContact;

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
    public $state;

    /**
     * @var string
     */
    public $zipCode;

    /**
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $invoiceStreet1;

    /**
     * @var string
     */
    public $invoiceStreet2;

    /**
     * @var string
     */
    public $invoiceCity;

    /**
     * @var string
     */
    public $invoiceState;

    /**
     * @var string
     */
    public $invoiceZipCode;

    /**
     * @var string
     */
    public $invoiceCountry;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var bool
     */
    public $suspendServicesIfPaymentIsOverdue;

    /**
     * @var int
     */
    public $suspensionDelay;

    /**
     * @var string
     */
    public $previousIsp;

    /**
     * @var string
     */
    public $registrationDate;

    /**
     * @var string
     */
    public $note;

    /**
     * @var bool
     */
    public $hasSuspendedService;

    /**
     * @var bool
     */
    public $hasOutage;

    /**
     * @var bool
     */
    public $hasOverdueInvoice;

    /**
     * @var string
     */
    public $accountBalance;

    /**
     * @var float
     */
    public $accountBalanceRaw;

    /**
     * @var string
     */
    public $accountCredit;

    /**
     * @var float
     */
    public $accountCreditRaw;

    /**
     * @var string
     */
    public $accountOutstanding;

    /**
     * @var float
     */
    public $accountOutstandingRaw;

    /**
     * @var array
     */
    public $contacts;

    /**
     * @var string
     */
    public $firstBillingEmail;

    /**
     * @var string
     */
    public $firstBillingPhone;

    /**
     * @var string
     */
    public $firstGeneralEmail;

    /**
     * @var string
     */
    public $firstGeneralPhone;

    public function getAttribute(string $name): string
    {
        return $this->attributes[$name] ?? '';
    }
}
