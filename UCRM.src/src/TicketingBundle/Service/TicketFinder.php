<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Service;

use AppBundle\Entity\ClientContact;
use AppBundle\Util\ImapMessageParser;
use Ddeboer\Imap\MessageInterface;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Entity\TicketComment;
use TicketingBundle\Entity\TicketStatusChange;

class TicketFinder
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findByNewIncomingEmail(MessageInterface $message): ?Ticket
    {
        foreach ($message->getInReplyTo() as $emailId) {
            $emailId = trim($emailId, '<>');
            if ($ticket = $this->findTicketByEmailId($emailId)) {
                return $ticket;
            }
            if ($ticket = $this->findTicketByNotificationEmailId($emailId)) {
                return $ticket;
            }
        }

        foreach ($message->getReferences() as $reference) {
            $reference = trim($reference, '<>');
            if ($ticket = $this->findTicketByEmailId($reference)) {
                return $ticket;
            }
            if ($ticket = $this->findTicketByNotificationEmailId($reference)) {
                return $ticket;
            }
        }

        if (($from = $message->getFrom())
            && ($from->getAddress() ?? false)
        ) {
            $pattern = '/ucrm_ticket_identification#(?P<ticketId>\d+)/';
            if (
                $ticket = $this->findTicketFromStringAndEmail(ImapMessageParser::getBodyText($message), $pattern, $from->getAddress())
            ) {
                return $ticket;
            }

            if (
                $ticket = $this->findTicketFromStringAndEmail(ImapMessageParser::getBodyHtml($message), $pattern, $from->getAddress())
            ) {
                return $ticket;
            }

            if (
                $ticket = $this->findTicketFromStringAndEmail($message->getSubject() ?: '', '/#(?P<ticketId>\d+)/', $from->getAddress())
            ) {
                return $ticket;
            }
        }

        return null;
    }

    private function findTicketByEmailId(string $emailId): ?Ticket
    {
        $ticketComment = $this->entityManager->getRepository(TicketComment::class)
            ->findOneBy(
                [
                    'emailId' => $emailId,
                ]
            );
        if ($ticketComment) {
            return $ticketComment->getTicket();
        }

        $ticketStatusChange = $this->entityManager->getRepository(TicketStatusChange::class)
            ->findOneBy(
                [
                    'emailId' => $emailId,
                ]
            );
        if ($ticketStatusChange) {
            return $ticketStatusChange->getTicket();
        }

        return null;
    }

    private function findTicketByNotificationEmailId(string $emailId): ?Ticket
    {
        $ticketComment = $this->entityManager->getRepository(TicketComment::class)
            ->findOneBy(
                [
                    'notificationEmailId' => $emailId,
                ]
            );

        return $ticketComment ? $ticketComment->getTicket() : null;
    }

    private function findTicketFromStringAndEmail(string $string, string $pattern, string $fromAddress): ?Ticket
    {
        if (
            ($matches = Strings::matchAll($string, $pattern))
            && ($ticket = $this->findTicketByIdsAndEmail(array_column($matches, 'ticketId'), $fromAddress))
        ) {
            return $ticket;
        }

        return null;
    }

    private function findTicketByIdsAndEmail(array $ticketIds, string $fromAddress): ?Ticket
    {
        $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy(
            [
                'id' => $ticketIds,
                'emailFromAddress' => $fromAddress,
            ],
            [
                'lastActivity' => 'DESC',
            ]
        );
        if ($ticket) {
            return $ticket;
        }

        $ticketComment = $this->entityManager->getRepository(TicketComment::class)->findOneBy(
            [
                'ticket' => $ticketIds,
                'emailFromAddress' => $fromAddress,
            ],
            [
                'createdAt' => 'DESC',
            ]
        );
        if ($ticketComment) {
            return $ticketComment->getTicket();
        }

        $clientRepository = $this->entityManager->getRepository(ClientContact::class);
        if ($client = $clientRepository->findExactlyOneClientByContactEmail($fromAddress)) {
            $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy(
                [
                    'id' => $ticketIds,
                    'client' => $client,
                ],
                [
                    'lastActivity' => 'DESC',
                ]
            );

            if ($ticket) {
                return $ticket;
            }
        }

        return null;
    }
}
