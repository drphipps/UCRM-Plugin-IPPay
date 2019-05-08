<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Fee;

interface FinancialItemFeeInterface extends FinancialItemInterface
{
    public function getFee(): ?Fee;

    public function setFee(?Fee $fee): void;
}
