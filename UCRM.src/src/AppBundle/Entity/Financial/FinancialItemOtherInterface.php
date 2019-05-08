<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

interface FinancialItemOtherInterface extends FinancialItemInterface
{
    public function getUnit(): ?string;

    public function setUnit(?string $unit): void;
}
