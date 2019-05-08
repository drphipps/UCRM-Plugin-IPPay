<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace ApiBundle\Map;

use JMS\Serializer\Annotation\ReadOnly;
use JMS\Serializer\Annotation\Type;

final class PaymentMap extends AbstractMap
{
    /**
     * @Type("integer")
     * @ReadOnly()
     */
    public $id;

    /**
     * @Type("integer")
     */
    public $clientId;

    /**
     * @Type("integer")
     *
     * @deprecated
     */
    public $invoiceId;

    /**
     * @Type("array<integer>")
     */
    public $invoiceIds = [];

    /**
     * @Type("integer")
     */
    public $method;

    /**
     * @Type("string")
     */
    public $checkNumber;

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
    public $currencyCode;

    /**
     * @Type("string")
     */
    public $note;

    /**
     * @Type("DateTime")
     * @ReadOnly()
     */
    public $receiptSentDate;

    /**
     * @Type("string")
     */
    public $providerName;

    /**
     * @Type("string")
     */
    public $providerPaymentId;

    /**
     * @Type("DateTime")
     */
    public $providerPaymentTime;

    /**
     * @Type("array<ApiBundle\Map\PaymentCoverMap>")
     * @ReadOnly()
     */
    public $paymentCovers = [];

    /**
     * @Type("double")
     * @ReadOnly()
     */
    public $creditAmount;

    /**
     * @Type("boolean")
     */
    public $applyToInvoicesAutomatically = false;

    /**
     * @Type("integer")
     */
    public $userId;
}
