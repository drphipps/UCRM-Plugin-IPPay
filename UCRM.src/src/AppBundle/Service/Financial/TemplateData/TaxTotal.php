<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial\TemplateData;

class TaxTotal
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $price;

    /**
     * @var float|null
     */
    public $priceRaw;
}
