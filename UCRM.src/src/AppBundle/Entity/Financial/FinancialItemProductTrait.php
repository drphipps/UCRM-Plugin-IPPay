<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity\Financial;

use AppBundle\Entity\Product;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait FinancialItemProductTrait
{
    /**
     * @var Product|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="product_id")
     */
    protected $product;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unit", type="string", length=50, nullable=true)
     * @Assert\Length(max = 50)
     */
    protected $unit;

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }
}
