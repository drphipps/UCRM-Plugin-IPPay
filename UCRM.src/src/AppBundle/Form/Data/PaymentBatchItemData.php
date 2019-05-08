<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentBatchItemData
{
    /**
     * @var Client|null
     *
     * @Assert\NotNull();
     */
    public $client;

    /**
     * @var float|null
     *
     * @Assert\NotNull();
     * @Assert\GreaterThan(0)
     */
    public $amount;

    /**
     * @var string|null
     */
    public $checkNumber;

    /**
     * @var string|null
     */
    public $note;

    /**
     * @var bool
     */
    public $sendReceipt = false;

    /**
     * @var array|Invoice[]
     */
    public $invoices = [];
}
