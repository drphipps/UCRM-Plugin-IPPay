<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\PaymentStripePending;
use Doctrine\ORM\EntityManagerInterface;

class PaymentStripePendingFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function handleDelete(PaymentStripePending $paymentPending): void
    {
        $this->em->remove($paymentPending);
        $this->em->flush();
    }

    public function handleNew(PaymentStripePending $paymentPending): void
    {
        $this->em->persist($paymentPending);
        $this->em->flush();
    }
}
