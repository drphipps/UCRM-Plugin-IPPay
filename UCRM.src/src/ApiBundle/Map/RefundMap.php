<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class RefundMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     */
    public $method;

    /**
     * @Type("DateTime")
     */
    public $createdDate;

    /**
     * @Type("double")
     */
    public $amount;

    /**
     * @Type("string")
     */
    public $note;

    /**
     * @Type("integer")
     */
    public $clientId;

    /**
     * @Type("string")
     */
    public $currencyCode;

    /**
     * @Type("array<ApiBundle\Map\PaymentCoverMap>")
     * @ReadOnly()
     */
    public $paymentCovers = [];
}
