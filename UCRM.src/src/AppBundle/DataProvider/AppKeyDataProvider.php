<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\AppKey;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class AppKeyDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(AppKey::class)
            ->createQueryBuilder('ak')
            ->andWhere('ak.plugin IS NULL')
            ->andWhere('ak.deletedAt IS NULL');
    }

    /**
     * Generates unique key to be used for an AppKey.
     *
     * There is possible race condition, if multiple AppKeys are created simultaneously, however
     * the chance is so small thanks to the random_bytes, that we can safely ignore it as this is far better,
     * than other solutions (calling facade from facade, transaction within transaction, etc.).
     */
    public function getUniqueKey(): string
    {
        do {
            $key = base64_encode(random_bytes(48));
        } while ($this->entityManager->getRepository(AppKey::class)->findOneBy(['key' => $key]));

        return $key;
    }
}
