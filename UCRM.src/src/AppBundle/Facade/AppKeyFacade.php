<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\AppKey;
use Doctrine\ORM\EntityManagerInterface;

class AppKeyFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleNew(AppKey $appKey): void
    {
        $this->entityManager->persist($appKey);
        $this->entityManager->flush();
    }

    public function handleEdit(AppKey $appKey): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(AppKey $appKey): void
    {
        $appKey->setDeletedAt(new \DateTime());
        $this->entityManager->flush();
    }
}
