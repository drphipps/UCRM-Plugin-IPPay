<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class TaxRecapitulation
{
    /**
     * @var string
     */
    public $priceUntaxed;

    /**
     * @var float
     */
    public $priceUntaxedRaw;

    /**
     * @var string
     */
    public $priceWithTax;
    /**
     * @var float
     */
    public $priceWithTaxRaw;

    /**
     * @var string
     */
    public $taxAmount;

    /**
     * @var float
     */
    public $taxAmountRaw;

    /**
     * @var string
     */
    public $taxName;

    /**
     * @var float|null
     */
    public $taxRate;
}
