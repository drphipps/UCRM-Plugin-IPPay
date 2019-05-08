<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\PaymentToken;
use Doctrine\ORM\EntityManager;

class PaymentTokenFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function handleEdit(PaymentToken $paymentToken): void
    {
        $this->em->flush($paymentToken);
    }
}
