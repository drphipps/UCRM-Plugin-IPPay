<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialItemOtherTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="unit", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $unit;

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }
}
