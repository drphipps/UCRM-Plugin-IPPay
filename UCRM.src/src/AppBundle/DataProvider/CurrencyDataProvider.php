<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyDataProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAllCurrencies(): array
    {
        return $this->entityManager->getRepository(Currency::class)->findBy(
            [],
            [
                'id' => 'ASC',
            ]
        );
    }

    /**
     * @return Currency[]
     */
    public function getAllCurrenciesCsvMapping(): array
    {
        return $this->entityManager->getRepository(Currency::class)
            ->createQueryBuilder('c', 'c.id')
            ->orderBy('c.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
