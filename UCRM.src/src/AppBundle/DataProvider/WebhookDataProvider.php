<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\WebhookAddress;
use AppBundle\Entity\WebhookEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class WebhookDataProvider
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
        return $this->entityManager->getRepository(WebhookAddress::class)
            ->createQueryBuilder('wha')
            ->andWhere('wha.deletedAt IS NULL');
    }

    public function getLogGridModel(WebhookAddress $webhookAddress = null): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(WebhookEvent::class)
            ->createQueryBuilder('whe')
            ->addSelect('string_agg_distinct(wher.responseCode, \',\') AS response_code')
            ->leftJoin('whe.requests', 'wher')
            ->addOrderBy('whe.id', 'DESC')
            ->groupBy('whe.id');

        if ($webhookAddress) {
            $qb->andWhere('wher.webhookAddress = ' . $webhookAddress->getId());
        }

        return $qb;
    }
}
