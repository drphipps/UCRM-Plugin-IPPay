<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\CustomAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class CustomAttributeDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return CustomAttribute[]
     */
    public function getAll(): array
    {
        return $this->entityManager->getRepository(CustomAttribute::class)->findBy(
            [],
            [
                'id' => 'ASC',
            ]
        );
    }

    /**
     * @return CustomAttribute[]
     */
    public function getByAttributeType(string $attributeType): array
    {
        return $this->entityManager->getRepository(CustomAttribute::class)->findBy(
            [
                'attributeType' => $attributeType,
            ],
            [
                'id' => 'ASC',
            ]
        );
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(CustomAttribute::class)
            ->createQueryBuilder('a')
            ->addSelect('a.attributeType AS a_attributeType');
    }
}
