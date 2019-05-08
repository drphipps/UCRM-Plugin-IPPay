<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class Invoice
{
    /**
     * @var string
     */
    public $status;

    /**
     * @var int
     */
    public $statusNumeric;

    /**
     * @var string
     */
    public $number;

    /**
     * @var string
     */
    public $createdDate;

    /**
     * @var string
     */
    public $createdDateISO8601;

    /**
     * @var string
     */
    public $dueDate;

    /**
     * @var string
     */
    public $dueDateISO8601;

    /**
     * @var string
     */
    public $notes;

    /**
     * @var bool
     */
    public $pricesWithTax;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @var string|null
     */
    public $firstServiceBillingPeriodType;

    /**
     * @var string|null
     */
    public $onlinePaymentLink;

    /**
     * @var string
     */
    public $taxableSupplyDate;

    /**
     * @var string
     */
    public $taxableSupplyDateISO8601;

    public function getAttribute(string $name): string
    {
        return $this->attributes[$name] ?? '';
    }
}
