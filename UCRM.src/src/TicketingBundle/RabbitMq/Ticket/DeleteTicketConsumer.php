<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\RabbitMq\Ticket;

use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TicketingBundle\Entity\Ticket;
use TicketingBundle\Service\Facade\TicketFacade;

class DeleteTicketConsumer extends AbstractConsumer
{
    /**
     * @var TicketFacade
     */
    private $ticketFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        TicketFacade $ticketFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->ticketFacade = $ticketFacade;
    }

    protected function getMessageClass(): string
    {
        return DeleteTicketMessage::class;
    }

    public function executeBody(array $data): int
    {
        $ticket = $this->entityManager->find(Ticket::class, $data['ticketId']);
        if (! $ticket) {
            $this->logger->warning(sprintf('Ticket %d not found.', $data['ticketId']));

            return self::MSG_REJECT;
        }

        $this->ticketFacade->handleDelete($ticket);

        $this->logger->info(sprintf('Ticket %d deleted.', $data['ticketId']));

        return self::MSG_ACK;
    }
}
