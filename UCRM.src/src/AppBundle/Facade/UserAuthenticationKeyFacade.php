<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use ApiBundle\Entity\UserAuthenticationKey;
use Doctrine\ORM\EntityManagerInterface;

class UserAuthenticationKeyFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleDelete(UserAuthenticationKey $authenticationKey): void
    {
        $this->entityManager->remove($authenticationKey);
        $this->entityManager->flush();
    }
}
