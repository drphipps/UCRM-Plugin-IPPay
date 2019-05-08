<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\CustomAttribute;
use Doctrine\ORM\EntityManagerInterface;

class CustomAttributeFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleNew(CustomAttribute $attribute): void
    {
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();
    }

    public function handleEdit(CustomAttribute $attribute): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(CustomAttribute $attribute): void
    {
        $this->entityManager->remove($attribute);
        $this->entityManager->flush();
    }
}
