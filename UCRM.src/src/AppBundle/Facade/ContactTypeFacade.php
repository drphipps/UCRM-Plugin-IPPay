<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ContactType;
use Doctrine\ORM\EntityManagerInterface;

class ContactTypeFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleNew(ContactType $contactType): void
    {
        $this->entityManager->persist($contactType);
        $this->entityManager->flush();
    }

    public function handleEdit(ContactType $contactType): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(ContactType $contactType): bool
    {
        if ($contactType->getId() >= ContactType::CONTACT_TYPE_MAX_SYSTEM_ID) {
            $this->entityManager->remove($contactType);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }
}
