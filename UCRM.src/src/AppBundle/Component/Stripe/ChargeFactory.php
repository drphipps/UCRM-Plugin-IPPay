<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\Stripe;

use AppBundle\Facade\PaymentFacade;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ChargeFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    public function __construct(EntityManager $entityManager, PaymentFacade $paymentFacade)
    {
        $this->entityManager = $entityManager;
        $this->paymentFacade = $paymentFacade;
    }

    public function create(bool $sandbox): Charge
    {
        return new Charge($this->entityManager, $this->paymentFacade, $sandbox);
    }
}
