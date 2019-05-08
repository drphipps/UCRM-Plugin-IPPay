<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\PaymentToken;
use Doctrine\ORM\EntityManagerInterface;

class OnlinePaymentFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleCreatePaymentToken(PaymentToken $token): void
    {
        $this->entityManager->transactional(
            function () use ($token) {
                $this->entityManager->persist($token);
            }
        );
    }
}
