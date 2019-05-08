<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class PaymentCoverMap extends AbstractMap
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
    public $invoiceId;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $paymentId;

    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $refundId;

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $amount;
}
