<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentBatchData
{
    /**
     * @var int
     *
     * @Assert\NotNull()
     */
    public $method;

    /**
     * @var \DateTime
     * @Assert\LessThanOrEqual("now")
     */
    public $createdDate;

    /**
     * @var ArrayCollection|PaymentBatchItemData[]
     * @Assert\Count(
     *     min = 1,
     *     minMessage = "You must add at least one payment."
     * )
     * @Assert\Valid()
     */
    public $payments;

    /**
     * @var bool
     */
    public $sendReceipt = false;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }
}
