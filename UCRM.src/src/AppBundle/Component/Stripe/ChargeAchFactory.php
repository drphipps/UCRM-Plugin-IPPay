<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Stripe;

use AppBundle\Facade\PaymentStripePendingFacade;
use Doctrine\ORM\EntityManagerInterface;

class ChargeAchFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentStripePendingFacade
     */
    private $paymentStripePendingFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentStripePendingFacade $paymentStripePendingFacade
    ) {
        $this->entityManager = $entityManager;
        $this->paymentStripePendingFacade = $paymentStripePendingFacade;
    }

    public function create(bool $sandbox): ChargeAch
    {
        return new ChargeAch($this->entityManager, $this->paymentStripePendingFacade, $sandbox);
    }
}
