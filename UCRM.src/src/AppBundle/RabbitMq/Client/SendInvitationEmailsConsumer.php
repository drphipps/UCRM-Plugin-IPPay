<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Client;

use AppBundle\Entity\Client;
use AppBundle\Exception\NoClientContactException;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\InvitationEmailSender;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SendInvitationEmailsConsumer extends AbstractConsumer
{
    /**
     * @var InvitationEmailSender
     */
    private $invitationEmailSender;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        InvitationEmailSender $invitationEmailSender
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->invitationEmailSender = $invitationEmailSender;
    }

    protected function getMessageClass(): string
    {
        return SendInvitationEmailsMessage::class;
    }

    public function executeBody(array $data): int
    {
        $clients = $this->entityManager->getRepository(Client::class)->getClientsWithoutInvitationEmail();

        foreach ($clients as $client) {
            try {
                $this->invitationEmailSender->send($client, EmailEnqueuer::PRIORITY_LOW);
            } catch (PublicUrlGeneratorException $exception) {
                $this->logger->error($exception->getMessage());

                return self::MSG_REJECT;
            } catch (NoClientContactException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return self::MSG_ACK;
    }
}
