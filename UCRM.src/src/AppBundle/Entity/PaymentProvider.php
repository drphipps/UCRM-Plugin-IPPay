<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class PaymentProvider
{
    const ID_CUSTOM = 1;
    const ID_PAYPAL = 2;
    const ID_STRIPE = 3;
    const ID_AUTHORIZE_NET = 4;
    const ID_IPPAY = 5;
    const ID_MERCADO_PAGO = 6;

    /**
     * @var int
     *
     * @ORM\Column(name = "provider_id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(length = 30)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(length = 100)
     */
    protected $paymentDetailsClass;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPaymentDetailsClass(): string
    {
        return $this->paymentDetailsClass;
    }
}
