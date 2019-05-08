<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\Type;

final class ServiceEditMap extends AbstractMap
{
    /**
     * @Type("integer")
     */
    public $servicePlanId;

    /**
     * @Type("integer")
     */
    public $servicePlanPeriodId;

    /**
     * @Type("string")
     */
    public $name;

    /**
     * @Type("double")
     */
    public $price;

    /**
     * @Type("integer")
     */
    public $invoicingPeriodType;

    /**
     * @Type("string")
     */
    public $invoiceLabel;

    /**
     * @Type("DateTime")
     */
    public $activeTo;

    /**
     * @Type("integer")
     */
    public $nextInvoicingDayAdjustment;

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
    public $contractEndDate;

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
     * @Type("double")
     */
    public $addressGpsLat;

    /**
     * @Type("double")
     */
    public $addressGpsLon;

    /**
     * @Type("string")
     */
    public $note;

    /**
     * @Type("string")
     */
    public $fccBlockId;
}
