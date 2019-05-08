<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Fee;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class QuoteItemFee extends QuoteItem implements FinancialItemFeeInterface
{
    use FinancialItemFeeTrait;

    /**
     * @var Fee|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Fee", inversedBy="quoteItems")
     * @ORM\JoinColumn(referencedColumnName="fee_id", onDelete="SET NULL")
     */
    protected $fee;
}
