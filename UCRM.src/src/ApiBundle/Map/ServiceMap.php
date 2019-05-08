<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

class ServiceMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $clientId;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $status;

    /**
     * @Type("string")
     */
    public $name;

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
    public $note;

    /**
     * @Type("double")
     */
    public $addressGpsLat;

    /**
     * @Type("double")
     */
    public $addressGpsLon;

    /**
     * @Type("integer")
     */
    public $servicePlanId;

    /**
     * @Type("integer")
     */
    public $servicePlanPeriodId;

    /**
     * @Type("double")
     */
    public $price;

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $hasIndividualPrice;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $totalPrice;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $currencyCode;

    /**
     * @Type("string")
     */
    public $invoiceLabel;

    /**
     * @Type("string")
     */
    public $contractId;

    /**
     * @Type("integer")
     */
    public $contractLengthType;

    /**
     * @Type("integer")
     */
    public $minimumContractLengthMonths;

    /**
     * @Type("DateTime")
     */
    public $activeFrom;

    /**
     * @Type("DateTime")
     */
    public $activeTo;

    /**
     * @Type("DateTime")
     */
    public $contractEndDate;

    /**
     * @Type("integer")
     */
    public $discountType;

    /**
     * @Type("double")
     */
    public $discountValue;

    /**
     * @Type("string")
     */
    public $discountInvoiceLabel;

    /**
     * @Type("DateTime")
     */
    public $discountFrom;

    /**
     * @Type("DateTime")
     */
    public $discountTo;

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
    public $invoicingStart;

    /**
     * @Type("integer")
     */
    public $invoicingPeriodType;

    /**
     * @Type("integer")
     */
    public $invoicingPeriodStartDay;

    /**
     * @Type("integer")
     */
    public $nextInvoicingDayAdjustment;

    /**
     * @Type("boolean")
     */
    public $invoicingProratedSeparately;

    /**
     * @Type("boolean")
     */
    public $invoicingSeparately;

    /**
     * @Type("boolean")
     */
    public $sendEmailsAutomatically;

    /**
     * @Type("boolean")
     */
    public $useCreditAutomatically;

    /**
     * @Type("string")
     * @ReadOnly()
     */
    public $servicePlanName;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $servicePlanPrice;

    /**
     * @Type("integer")
     */
    public $servicePlanPeriod;

    /**
     * @Type("float")
     * @ReadOnly()
     */
    public $downloadSpeed;

    /**
     * @Type("float")
     * @ReadOnly()
     */
    public $uploadSpeed;

    /**
     * @Type("array<string>")
     * @ReadOnly()
     */
    public $ipRanges = [];

    /**
     * @Type("boolean")
     * @ReadOnly()
     */
    public $hasOutage;

    /**
     * @Type("string")
     */
    public $fccBlockId;

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $lastInvoicedDate;
}
