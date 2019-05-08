<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

class ClientMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     */
    public $userIdent;

    /**
     * @Type("string")
     */
    public $previousIsp;

    /**
     * @Type("boolean")
     */
    public $isLead;

    /**
     * @Type("integer")
     */
    public $clientType;

    /**
     * @Type("string")
     */
    public $companyName;

    /**
     * @Type("string")
     */
    public $companyRegistrationNumber;

    /**
     * @Type("string")
     */
    public $companyTaxId;

    /**
     * @Type("string")
     */
    public $companyWebsite;

    /**
     * @Type("string")
     */
    public $street1;

    /**
     * @Type("string")
     */
    public $street2;

    /**
     * @Type("string")
     */
    public $city;

    /**
     * @Type("integer")
     */
    public $countryId;

    /**
     * @Type("integer")
     */
    public $stateId;

    /**
     * @Type("string")
     */
    public $zipCode;

    /**
     * @Type("string")
     */
    public $invoiceStreet1;

    /**
     * @Type("string")
     */
    public $invoiceStreet2;

    /**
     * @Type("string")
     */
    public $invoiceCity;

    /**
     * @Type("integer")
     */
    public $invoiceStateId;

    /**
     * @Type("integer")
     */
    public $invoiceCountryId;

    /**
     * @Type("string")
     */
    public $invoiceZipCode;

    /**
     * @Type("boolean")
     */
    public $invoiceAddressSameAsContact;

    /**
     * @Type("string")
     */
    public $note;

    /**
     * @Type("boolean")
     */
    public $sendInvoiceByPost;

    /**
     * @Type("integer")
     */
    public $invoiceMaturityDays;

    /**
     * @Type("boolean")
     */
    public $stopServiceDue;

    /**
     * @Type("integer")
     */
    public $stopServiceDueDays;

    /**
     * @Type("integer")
     */
    public $organizationId;

    /**
     * @Type("integer")
     */
    public $tax1Id;

    /**
     * @Type("integer")
     */
    public $tax2Id;

    /**
     * @Type("integer")
     */
    public $tax3Id;

    /**
     * @Type("DateTime")
     */
    public $registrationDate;

    /**
     * @Type("string")
     */
    public $companyContactFirstName;

    /**
     * @Type("string")
     */
    public $companyContactLastName;

    /**
     * @Type("boolean")
     */
    public $isActive;

    /**
     * @Type("string")
     */
    public $firstName;

    /**
     * @Type("string")
     */
    public $lastName;

    /**
     * @Type("string")
     */
    public $username;

    /**
     * @Type("array<ApiBundle\Map\ClientContactMap>")
     */
    public $contacts = [];

    /**
     * @Type("array<ApiBundle\Map\ClientAttributeMap>")
     */
    public $attributes = [];

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $accountBalance;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $accountCredit;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $accountOutstanding;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $currencyCode;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $organizationName;

    /**
     * @Type("array<ApiBundle\Map\ClientBankAccountMap>")
     * @ReadOnly()
     */
    public $bankAccounts = [];

    /**
     * @Type("array<ApiBundle\Map\ClientTagMap>")
     * @ReadOnly()
     */
    public $tags = [];

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $invitationEmailSentDate;

    /**
     * @Type("string")
     */
    public $avatarColor;

    /**
     * @Type("double")
     */
    public $addressGpsLat;

    /**
     * @Type("double")
     */
    public $addressGpsLon;

    /**
     * @Type("boolean")
     */
    public $isArchived;

    /**
     * @Type("boolean")
     */
    public $generateProformaInvoices;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $usesProforma;
}
