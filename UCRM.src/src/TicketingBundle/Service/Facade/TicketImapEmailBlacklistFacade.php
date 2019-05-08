<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\DataProvider\TicketImapEmailBlacklistDataProvider;
use TicketingBundle\Entity\TicketImapEmailBlacklist;

class TicketImapEmailBlacklistFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TicketImapEmailBlacklistDataProvider
     */
    private $blacklistDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        TicketImapEmailBlacklistDataProvider $blacklistDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->blacklistDataProvider = $blacklistDataProvider;
    }

    /**
     * @param TicketImapEmailBlacklist[] $ticketImapEmailBlacklists
     */
    public function createMultiple(array $ticketImapEmailBlacklists): int
    {
        $created = 0;
        foreach ($ticketImapEmailBlacklists as $ticketImapEmailBlacklist) {
            if (! $this->blacklistDataProvider->exists($ticketImapEmailBlacklist->getEmailAddress())) {
                $this->entityManager->persist($ticketImapEmailBlacklist);
                ++$created;
            }
        }
        $this->entityManager->flush();

        return $created;
    }

    public function handleCreate(TicketImapEmailBlacklist $ticketImapEmailBlacklist): void
    {
        if (! $this->blacklistDataProvider->exists($ticketImapEmailBlacklist->getEmailAddress())) {
            $this->entityManager->persist($ticketImapEmailBlacklist);
            $this->entityManager->flush();
        }
    }

    public function handleUpdate(TicketImapEmailBlacklist $ticketImapEmailBlacklist): void
    {
        $this->entityManager->flush();
    }

    public function handleDelete(TicketImapEmailBlacklist $ticketImapEmailBlacklist): void
    {
        $this->entityManager->remove($ticketImapEmailBlacklist);
        $this->entityManager->flush();
    }
}
