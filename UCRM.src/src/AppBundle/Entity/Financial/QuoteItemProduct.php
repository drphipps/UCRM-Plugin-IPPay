<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class QuoteItemProduct extends QuoteItem implements FinancialItemProductInterface
{
    use FinancialItemProductTrait;
}
