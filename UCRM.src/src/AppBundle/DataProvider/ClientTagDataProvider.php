<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\DataProvider;

use AppBundle\Entity\Client;
use AppBundle\Entity\ClientTag;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class ClientTagDataProvider
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGridModel(): QueryBuilder
    {
        return $this->entityManager->getRepository(ClientTag::class)->createQueryBuilder('t');
    }

    public function getClientTags(): array
    {
        return $this->entityManager->getRepository(ClientTag::class)
            ->findBy(
                [],
                [
                    'id' => 'ASC',
                ]
            );
    }

    public function getAllPossibleTagsForClient(Client $client): array
    {
        return $this->entityManager->getRepository(ClientTag::class)
            ->createQueryBuilder('ct')
            ->where(':client NOT MEMBER OF ct.clients')
            ->setParameter('client', $client)
            ->orderBy('ct.name')
            ->getQuery()
            ->getResult();
    }

    public function getUsedTagsFilter(bool $onlyLeads = false): array
    {
        $qb = $this->entityManager->getRepository(ClientTag::class)
            ->createQueryBuilder('t');

        if ($onlyLeads) {
            $qb->innerJoin('t.clients', 'tc', Join::WITH, 'tc.isLead = true');
        } else {
            $qb->innerJoin('t.clients', 'tc');
        }

        /** @var ClientTag[] $tags */
        $tags = $qb->groupBy('t.id')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $filter = [];
        foreach ($tags as $tag) {
            $filter[$tag->getId()] = [
                'label' => $tag->getName(),
                'attributes' => [
                    'data-color-text' => $tag->getColorText(),
                    'data-color-background' => $tag->getColorBackground(),
                ],
            ];
        }

        return $filter;
    }
}
