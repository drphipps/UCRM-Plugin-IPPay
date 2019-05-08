<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace TicketingBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use TicketingBundle\Entity\TicketImapInbox;

class TicketImapInboxDataProvider
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
        return $this->entityManager->getRepository(TicketImapInbox::class)
            ->createQueryBuilder('tii')
            ->addSelect('tg.name as tg_ticketGroupName')
            ->leftJoin('tii.ticketGroup', 'tg');
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(TicketImapInbox::class)->findAll();
    }

    public function findDefault(): ?TicketImapInbox
    {
        return $this->entityManager->getRepository(TicketImapInbox::class)->findOneBy(
            [
                'isDefault' => true,
            ]
        );
    }
}
