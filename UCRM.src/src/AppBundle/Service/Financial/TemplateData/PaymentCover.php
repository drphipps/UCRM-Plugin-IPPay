<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class PaymentCover
{
    /**
     * @var string
     */
    public $amount;

    /**
     * @var float
     */
    public $amountRaw;

    /**
     * @var string
     */
    public $invoiceNumber;

    /**
     * @var string
     */
    public $invoiceTotal;

    /**
     * @var float
     */
    public $invoiceTotalRaw;

    /**
     * @var string
     */
    public $invoiceBalanceDue;

    /**
     * @var float
     */
    public $invoiceBalanceDueRaw;

    /**
     * @var string
     */
    public $invoiceCreatedDate;

    /**
     * @var string
     */
    public $invoiceDueDate;

    /**
     * @var array
     */
    public $invoiceAttributes;

    public function getInvoiceAttribute(string $name): string
    {
        return $this->invoiceAttributes[$name] ?? '';
    }
}
