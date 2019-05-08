<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

class ServiceDeleteData
{
    /**
     * @var bool
     */
    public $keepServiceDevices = true;

    /**
     * @var bool
     */
    public $keepRelatedInvoices = true;

    /**
     * @var bool
     */
    public $keepRelatedQuotes = true;
}
