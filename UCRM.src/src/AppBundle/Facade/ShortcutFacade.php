<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Shortcut;
use Doctrine\ORM\EntityManagerInterface;

class ShortcutFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleNew(Shortcut $shortcut): void
    {
        $this->entityManager->persist($shortcut);
        $this->entityManager->flush();
    }

    public function handleEdit(Shortcut $shortcut): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(Shortcut $shortcut): void
    {
        $shortcut->getUser()->removeShortcut($shortcut);
        $this->entityManager->remove($shortcut);
        $this->entityManager->flush();
    }
}
