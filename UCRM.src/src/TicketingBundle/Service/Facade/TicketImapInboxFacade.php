<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service\Facade;

use Doctrine\ORM\EntityManagerInterface;
use TicketingBundle\Entity\TicketImapInbox;

class TicketImapInboxFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleCreate(TicketImapInbox $ticketImapInbox): void
    {
        $this->handleDefaultInbox($ticketImapInbox);
        $this->entityManager->persist($ticketImapInbox);
        $this->entityManager->flush();
    }

    public function handleUpdate(TicketImapInbox $ticketImapInbox): void
    {
        $this->handleDefaultInbox($ticketImapInbox);
        $this->entityManager->flush();
    }

    public function handleDelete(TicketImapInbox $ticketImapInbox): void
    {
        $this->entityManager->remove($ticketImapInbox);
        $this->entityManager->flush();
    }

    public function getDefaultImportStartDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify('-30 hours');
    }

    private function handleDefaultInbox(TicketImapInbox $ticketImapInbox): void
    {
        $default = $this->entityManager->getRepository(TicketImapInbox::class)->findOneBy(
            [
                'isDefault' => true,
            ]
        );

        if (! $default) {
            $ticketImapInbox->setIsDefault(true);
        } elseif ($ticketImapInbox->isDefault() && $default->getId() !== $ticketImapInbox->getId()) {
            $default->setIsDefault(false);
        }
    }
}
