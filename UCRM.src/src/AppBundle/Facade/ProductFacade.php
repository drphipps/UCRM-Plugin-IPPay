<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Facade;

use AppBundle\Entity\Product;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class ProductFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function handleCreate(Product $product)
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function handleUpdate(Product $product)
    {
        $this->em->flush();
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL');
    }

    public function handleDelete(Product $product): bool
    {
        if (! $this->setDeleted($product)) {
            return false;
        }

        $this->em->flush();

        return true;
    }

    /**
     * @return array [$deleted, $failed]
     */
    public function handleDeleteMultiple(array $ids): array
    {
        $products = $this->em->getRepository(Product::class)->findBy(
            [
                'id' => $ids,
                'deletedAt' => null,
            ]
        );

        $count = count($products);
        $deleted = 0;

        foreach ($products as $product) {
            if (! $this->setDeleted($product)) {
                continue;
            }

            ++$deleted;
        }

        if ($deleted > 0) {
            $this->em->flush();
        }

        return [$deleted, $count - $deleted];
    }

    public function getAllProducts(): array
    {
        $repository = $this->em->getRepository(Product::class);

        return $repository->findBy(['deletedAt' => null], ['id' => 'ASC']);
    }

    private function setDeleted(Product $product): bool
    {
        $product->setDeletedAt(new \DateTime());

        return true;
    }
}
