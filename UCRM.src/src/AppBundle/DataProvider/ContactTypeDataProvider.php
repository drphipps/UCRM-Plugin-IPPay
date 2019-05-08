<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class ContactTypeDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGeneralType(): ContactType
    {
        return $this->entityManager->getRepository(ContactType::class)->find(ContactType::IS_CONTACT);
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(ContactType::class)->createQueryBuilder('c');
    }
}
