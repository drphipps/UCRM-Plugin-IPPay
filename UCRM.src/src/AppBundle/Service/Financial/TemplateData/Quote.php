<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class Quote
{
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
    public $notes;

    /**
     * @var bool
     */
    public $pricesWithTax;

    /**
     * @var string
     */
    public $createdDateISO8601;

    /**
     * @var string|null
     */
    public $firstServiceBillingPeriodType;
}
