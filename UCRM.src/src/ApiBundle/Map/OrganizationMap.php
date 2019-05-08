<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class OrganizationMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("string")
     */
    public $name;

    /**
     * @Type("string")
     */
    public $registrationNumber;

    /**
     * @Type("string")
     */
    public $taxId;

    /**
     * @Type("string")
     */
    public $phone;

    /**
     * @Type("string")
     */
    public $email;

    /**
     * @Type("string")
     */
    public $website;

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
    public $stateId;

    /**
     * @Type("integer")
     */
    public $countryId;

    /**
     * @Type("integer")
     */
    public $currencyId;

    /**
     * @Type("string")
     */
    public $zipCode;

    /**
     * @Type("boolean")
     */
    public $selected;
}
